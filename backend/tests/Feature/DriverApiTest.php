<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Customer;
use App\Models\DailyBooking;
use App\Models\Driver;
use App\Models\MonthlyContract;
use App\Models\MonthlyDriver;
use App\Models\Operator;
use App\Models\User;
use App\Policies\DriverPolicy;
use App\Services\DriverAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverApiTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_merge(['full_name' => 'Ko Driver', 'phone_number' => '0912345678', 'driver_status' => 'Available', 'note' => 'Airport route'], $extra);
    }

    private function booking(Driver $driver, array $extra = []): DailyBooking
    {
        return DailyBooking::create(array_merge(['guest_company_name' => 'Daily Company', 'driver_id' => $driver->id, 'operator_name' => 'Operator snapshot', 'start_date' => '2026-11-01', 'end_date' => '2026-11-10', 'status' => 'Confirm'], $extra));
    }

    private function contract(MonthlyDriver $driver, array $extra = []): MonthlyContract
    {
        return MonthlyContract::create(array_merge(['contract_no' => 'MC-DRIVER-1', 'customer_name' => 'Monthly Company', 'assignment_type' => 'Driver Only', 'driver_id' => $driver->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-10', 'monthly_rate' => '100', 'rental_currency' => 'MMK', 'rental_status' => 'Driver only', 'status' => 'Active'], $extra));
    }

    public function test_all_actions_require_session_and_policy_denials_are_respected(): void
    {
        foreach (['', '/summary', '/options', '/1'] as $path) {
            $this->getJson('/api/drivers'.$path)->assertUnauthorized();
        }
        $this->postJson('/api/drivers', [])->assertUnauthorized();
        $this->putJson('/api/drivers/1', [])->assertUnauthorized();
        $this->deleteJson('/api/drivers/1')->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        Gate::policy(Driver::class, DenyDriverPolicy::class);
        $this->getJson('/api/drivers')->assertForbidden();
        $this->postJson('/api/drivers', $this->data())->assertForbidden();
    }

    public function test_crud_both_scopes_required_fields_and_license_preservation(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/drivers', [])->assertUnprocessable()->assertJsonValidationErrors(['full_name', 'phone_number', 'driver_status']);
        $this->postJson('/api/drivers', $this->data(['driver_status' => 'On Trip']))->assertUnprocessable();
        $daily = $this->postJson('/api/drivers', $this->data())->assertCreated()->json('data.id');
        $monthly = $this->postJson('/api/drivers?resource_type=monthly', $this->data(['full_name' => 'Monthly Driver']))->assertCreated()->json('data.id');
        $this->assertSame($daily, $monthly); // Equal IDs still refer to independent records.
        Driver::findOrFail($daily)->update(['license_no' => 'EXISTING-LICENSE']);
        $this->putJson('/api/drivers/'.$daily, $this->data(['full_name' => 'Renamed', 'license_no' => null]))->assertOk()->assertJsonPath('data.license_no', 'EXISTING-LICENSE');
        $this->getJson('/api/drivers/'.$daily)->assertOk()->assertJsonPath('data.full_name', 'Renamed');
        $this->getJson('/api/drivers/'.$monthly.'?resource_type=monthly')->assertJsonPath('data.full_name', 'Monthly Driver');
        $this->getJson('/api/drivers?resource_type=invalid')->assertUnprocessable();
        $this->getJson('/api/drivers?month=2026-13')->assertUnprocessable();
        $this->deleteJson('/api/drivers/'.$daily)->assertNoContent();
        $this->assertDatabaseHas('monthly_drivers', ['id' => $monthly]);
        $this->deleteJson('/api/drivers/'.$monthly.'?resource_type=monthly')->assertNoContent();
    }

    public function test_search_status_ten_row_pagination_and_global_summary(): void
    {
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 12; $i++) {
            Driver::create($this->data(['full_name' => sprintf('Driver %02d', $i), 'driver_status' => $i === 0 ? 'Leave' : 'Available']));
        }
        $this->getJson('/api/drivers?per_page=100')->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 12)->assertJsonPath('data.0.full_name', 'Driver 00');
        $this->getJson('/api/drivers?page=2')->assertJsonCount(2, 'data');
        $this->getJson('/api/drivers?status=Leave&search=Airport')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/drivers?search=091234')->assertJsonPath('meta.total', 12);
        $this->getJson('/api/drivers/summary?status=Leave')->assertJsonPath('data.total', 12)->assertJsonPath('data.available', 11)->assertJsonPath('data.leave', 1);
        $driver = Driver::first();
        $this->booking($driver);
        $this->getJson('/api/drivers?search=Daily%20Company')->assertJsonPath('meta.total', 1);
        $driver->bookings()->update(['status' => 'Completed']);
        $this->getJson('/api/drivers?search=Daily%20Company')->assertJsonPath('meta.total', 0); // Legacy search uses active assignment names.
    }

    public function test_month_inclusive_history_vehicle_display_and_referenced_deletion(): void
    {
        $driver = Driver::create($this->data());
        $car = Car::create(['car_type' => 'Van', 'plate_no' => 'DRIVER-CAR', 'model_name' => 'Van', 'seat_capacity' => 8, 'availability_status' => 'Available']);
        $booking = $this->booking($driver, ['car_id' => $car->id, 'start_date' => '2025-01-31', 'end_date' => '2025-02-01', 'status' => 'Completed']);
        $this->booking($driver, ['guest_company_name' => 'Cancelled Guest', 'status' => 'Cancelled']);
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/drivers?month=2025-02')->assertJsonPath('data.0.assignments.0.name', 'Daily Company')->assertJsonPath('data.0.assignments.0.vehicle', 'DRIVER-CAR');
        $this->getJson('/api/drivers?month=2025-03')->assertJsonCount(0, 'data.0.assignments');
        $this->getJson('/api/drivers?month=2026-11')->assertJsonCount(0, 'data.0.assignments');
        $this->putJson('/api/drivers/'.$driver->id, $this->data(['full_name' => 'Renamed', 'driver_status' => 'Inactive']))->assertOk();
        $this->deleteJson('/api/drivers/'.$driver->id)->assertUnprocessable();
        $this->assertSame($driver->id, $booking->fresh()->driver_id);
        $this->assertSame('Daily Company', $booking->fresh()->guest_company_name);
    }

    public function test_date_options_are_scope_specific_and_cancelled_records_do_not_block(): void
    {
        $daily = Driver::create($this->data());
        $monthly = MonthlyDriver::create($this->data());
        $booking = $this->booking($daily);
        $contract = $this->contract($monthly);
        $this->actingAs(User::factory()->create());
        $dates = 'date_from=2026-11-10&date_to=2026-11-11';
        $this->getJson('/api/drivers/options?'.$dates)->assertJsonPath('data.drivers.0.available', false);
        $this->getJson('/api/drivers/options?'.$dates.'&except_id='.$booking->id)->assertJsonPath('data.drivers.0.available', true);
        $booking->update(['status' => 'Cancelled']);
        $this->getJson('/api/drivers/options?'.$dates)->assertJsonPath('data.drivers.0.available', true);
        $this->getJson('/api/drivers/options?resource_type=monthly&'.$dates)->assertJsonPath('data.drivers.0.available', false);
        $this->getJson('/api/drivers?resource_type=monthly&search=MC-DRIVER')->assertJsonPath('meta.total', 1);
        $this->deleteJson('/api/drivers/'.$monthly->id.'?resource_type=monthly')->assertUnprocessable();
        $contract->update(['status' => 'Cancelled']);
        $this->getJson('/api/drivers/options?resource_type=monthly&'.$dates)->assertJsonPath('data.drivers.0.available', true);
        $this->getJson('/api/drivers/options?date_from=2026-11-01')->assertUnprocessable();
    }

    public function test_confirmed_daily_overlap_reassignment_leave_and_historical_edits(): void
    {
        $daily = Driver::create($this->data());
        $second = Driver::create($this->data(['full_name' => 'Second']));
        $op = Operator::create(['full_name' => 'Dispatcher', 'operator_status' => 'Active']);
        $existing = $this->booking($daily);
        $this->actingAs(User::factory()->create());
        $input = ['guest_company_name' => 'API Guest', 'driver_id' => $daily->id, 'operator_id' => $op->id, 'even_odd' => 'Even', 'start_date' => '2026-11-10', 'end_date' => '2026-11-11', 'status' => 'Confirm'];
        $this->postJson('/api/daily-bookings', $input)->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->putJson('/api/daily-bookings/'.$existing->id, $input)->assertOk(); // own row is excluded
        $input['driver_id'] = $second->id;
        $this->postJson('/api/daily-bookings', $input)->assertCreated(); // reassignment uses the actual selected driver
        $second->update(['driver_status' => 'Leave']);
        $this->postJson('/api/daily-bookings', $input)->assertUnprocessable()->assertJsonValidationErrors('driver_id');
        $daily->update(['driver_status' => 'Inactive']);
        $input['driver_id'] = $daily->id;
        $input['status'] = 'Completed';
        $this->putJson('/api/daily-bookings/'.$existing->id, $input)->assertOk();
        $input['status'] = 'Confirm';
        $this->putJson('/api/daily-bookings/'.$existing->id, $input)->assertUnprocessable()->assertJsonValidationErrors('driver_id');
    }

    public function test_monthly_driver_overlap_and_pool_independence(): void
    {
        $monthly = MonthlyDriver::create($this->data());
        $daily = Driver::create($this->data());
        $existing = $this->contract($monthly);
        $this->booking($daily, ['status' => 'Pending']);
        $this->actingAs(User::factory()->create());
        $customer = Customer::create(['company_name' => 'Company', 'customer_status' => 'Active']);
        $input = ['customer_id' => $customer->id, 'customer_name' => 'Company', 'assignment_type' => 'Driver Only', 'driver_id' => $monthly->id, 'start_date' => '2026-11-10', 'end_date' => '2026-11-12', 'monthly_rate' => '100', 'rental_currency' => 'MMK', 'rental_status' => 'Driver only', 'insurance_price' => '0', 'status' => 'Active'];
        $this->postJson('/api/monthly-contracts', $input)->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $existing->update(['status' => 'Cancelled']);
        $this->postJson('/api/monthly-contracts', $input)->assertCreated();
        $monthly->update(['driver_status' => 'Leave']);
        $input['start_date'] = '2026-12-01';
        $input['end_date'] = '2026-12-02';
        $this->postJson('/api/monthly-contracts', $input)->assertUnprocessable()->assertJsonValidationErrors('driver_id');
    }

    public function test_old_daily_enum_values_are_used_without_status_rewriting(): void
    {
        Schema::shouldReceive('getColumns')->once()->with('drivers')->andReturn([['name' => 'driver_status', 'type' => "enum('Available','On Trip','Leave','Inactive')"]]);
        $this->assertSame(['Available', 'On Trip', 'Leave', 'Inactive'], app(DriverAvailabilityService::class)->statuses('daily'));
    }

    public function test_fleet_and_maintenance_show_driver_assignments_without_mutating_driver_history(): void
    {
        $this->actingAs(User::factory()->create());
        $driver = Driver::create($this->data());
        $car = Car::create(['car_type' => 'Van', 'plate_no' => 'DRIVER-MAINT', 'model_name' => 'Van', 'seat_capacity' => 8, 'availability_status' => 'Available']);
        $booking = $this->booking($driver, ['car_id' => $car->id, 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString()]);
        $input = ['car_id' => $car->id, 'maintenance_type' => 'Inspection', 'title' => 'Driver vehicle inspection', 'scheduled_date' => now()->toDateString(), 'currency' => 'MMK', 'status' => 'Scheduled'];
        $id = $this->postJson('/api/maintenances', $input)->assertCreated()->json('data.id');
        $this->getJson('/api/maintenances/'.$id)->assertJsonPath('data.assignments.0.driver', $driver->full_name);
        $this->getJson('/api/cars/'.$car->id)->assertJsonPath('data.assignments.0.driver_id', $driver->id);
        $input['status'] = 'In Progress';
        $this->putJson('/api/maintenances/'.$id, $input)->assertUnprocessable();
        $booking->update(['status' => 'Cancelled']);
        $this->putJson('/api/maintenances/'.$id, $input)->assertOk();
        $this->assertSame('Available', $driver->fresh()->driver_status);
        $this->assertSame($driver->id, $booking->fresh()->driver_id);
        $this->getJson('/api/drivers/options?date_from='.now()->toDateString().'&date_to='.now()->addDay()->toDateString())->assertJsonPath('data.drivers.0.available', true);
    }
}

class DenyDriverPolicy extends DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }
}
