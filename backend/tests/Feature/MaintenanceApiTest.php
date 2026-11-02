<?php
namespace Tests\Feature;

use App\Models\{Car, MonthlyCar, DailyBooking, MonthlyContract, Operator, User, VehicleMaintenance};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceApiTest extends TestCase
{
    use RefreshDatabase;
    private Car $car;
    private User $user;
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->car = Car::create(['car_type' => 'Van', 'plate_no' => 'MAINT-1', 'model_name' => 'Van', 'seat_capacity' => 8, 'availability_status' => 'Available']);
    }
    private function data(array $extra = []): array { return array_merge(['car_id' => $this->car->id, 'monthly_car_id' => null, 'maintenance_type' => 'Preventive', 'title' => 'Oil service', 'scheduled_date' => now()->toDateString(), 'status' => 'Scheduled', 'currency' => 'MMK', 'estimated_cost' => '100.00', 'actual_cost' => null], $extra); }
    public function test_authentication_validation_crud_and_transitions(): void
    {
        $this->getJson('/api/maintenances')->assertUnauthorized();
        $this->actingAs($this->user);
        $this->postJson('/api/maintenances', $this->data(['car_id' => null]))->assertUnprocessable();
        $this->postJson('/api/maintenances', $this->data(['actual_cost' => -1, 'maintenance_type' => 'Invalid']))->assertUnprocessable()->assertJsonValidationErrors(['actual_cost', 'maintenance_type']);
        $id = $this->postJson('/api/maintenances', $this->data())->assertCreated()->json('data.id');
        $this->getJson('/api/cars/'.$this->car->id)->assertJsonPath('data.effective_status', 'Available');
        $this->putJson('/api/maintenances/'.$id, $this->data(['status' => 'Completed']))->assertUnprocessable();
        $this->putJson('/api/maintenances/'.$id, $this->data(['status' => 'In Progress']))->assertOk();
        $this->getJson('/api/cars/'.$this->car->id)->assertJsonPath('data.effective_status', 'Maintenance');
        $this->assertSame('Available', $this->car->fresh()->availability_status);
        $this->postJson('/api/maintenances', $this->data(['status' => 'In Progress']))->assertUnprocessable();
        $this->deleteJson('/api/maintenances/'.$id)->assertUnprocessable();
        $this->car->update(['availability_status' => 'Assigned']);
        $this->putJson('/api/maintenances/'.$id, $this->data(['status' => 'Completed', 'actual_cost' => '25.50']))->assertOk();
        $this->getJson('/api/cars/'.$this->car->id)->assertJsonPath('data.effective_status', 'Assigned');
        $this->putJson('/api/maintenances/'.$id, $this->data(['status' => 'In Progress']))->assertUnprocessable();
        $this->deleteJson('/api/cars/'.$this->car->id)->assertUnprocessable();
        $this->getJson('/api/maintenances?search=MAINT-1&status=Completed&per_page=1')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/maintenances/'.$id)->assertOk()->assertJsonPath('data.actual_cost', '25.50');
    }
    public function test_both_vehicle_pools_and_secondary_bookings_block_start(): void
    {
        $this->actingAs($this->user);
        $monthly = MonthlyCar::create($this->car->only(['car_type', 'plate_no', 'model_name', 'seat_capacity', 'availability_status']));
        $this->postJson('/api/maintenances', $this->data(['monthly_car_id' => $monthly->id]))->assertUnprocessable();
        DailyBooking::create(['guest_company_name' => 'Booked', 'secondary_car_id' => $this->car->id, 'operator_name' => 'Snapshot', 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString(), 'status' => 'Pending']);
        $this->postJson('/api/maintenances', $this->data())->assertCreated(); // scheduling does not block assignments
        $this->postJson('/api/maintenances', $this->data(['status' => 'In Progress']))->assertUnprocessable()->assertJsonValidationErrors('started_at');
        MonthlyContract::create(['contract_no' => 'MC-TEST', 'customer_name' => 'Monthly Guest', 'assignment_type' => 'Vehicle Only', 'car_id' => $monthly->id, 'start_date' => now()->addDay()->toDateString(), 'end_date' => now()->addMonth()->toDateString(), 'monthly_rate' => 0, 'rental_currency' => 'MMK', 'rental_status' => 'Car only', 'status' => 'Active']);
        $this->postJson('/api/maintenances', $this->data(['car_id' => null, 'monthly_car_id' => $monthly->id, 'status' => 'In Progress']))->assertUnprocessable();
    }
    public function test_active_maintenance_blocks_booking_and_monthly_service_assignments(): void
    {
        $this->actingAs($this->user);
        $id = $this->postJson('/api/maintenances', $this->data(['status' => 'In Progress']))->assertCreated()->json('data.id');
        $op = Operator::create(['full_name' => 'Dispatcher', 'operator_status' => 'Active']);
        $booking = ['guest_company_name' => 'Guest', 'car_id' => $this->car->id, 'operator_id' => $op->id, 'even_odd' => 'Even', 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString(), 'status' => 'Pending'];
        $this->postJson('/api/daily-bookings', $booking)->assertUnprocessable()->assertJsonValidationErrors('car_id');
        $this->putJson('/api/maintenances/'.$id, $this->data(['status' => 'Cancelled']))->assertOk();
        $this->postJson('/api/daily-bookings', $booking)->assertCreated();
        $this->deleteJson('/api/maintenances/'.$id)->assertUnprocessable();
        $monthly = MonthlyCar::create($this->car->only(['car_type', 'plate_no', 'model_name', 'seat_capacity', 'availability_status']));
        $this->postJson('/api/maintenances', $this->data(['car_id' => null, 'monthly_car_id' => $monthly->id, 'status' => 'In Progress']))->assertCreated();
        try {
            app(\App\Services\MonthlyContractService::class)->save(['car_id' => $monthly->id, 'status' => 'Active', 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString()]);
            $this->fail('Monthly assignment should be blocked');
        } catch (\Illuminate\Validation\ValidationException $error) { $this->assertArrayHasKey('car_id', $error->errors()); }
    }
    public function test_currency_totals_period_filters_and_safe_deletion(): void
    {
        $this->actingAs($this->user);
        foreach ([['MMK', '25.50'], ['MMK', '4.50'], ['USD', '9.25']] as [$currency, $amount]) $this->postJson('/api/maintenances', $this->data(['currency' => $currency, 'actual_cost' => $amount]))->assertCreated();
        $summary = $this->getJson('/api/maintenances/summary?date_from='.now()->toDateString().'&date_to='.now()->toDateString())->assertOk()->assertJsonPath('data.total', 3);
        $this->assertSame(['MMK' => '30.00', 'USD' => '9.25'], collect($summary->json('data.actual_costs'))->pluck('total', 'currency')->all());
        $this->getJson('/api/maintenances/summary?date_from=2000-01-01&date_to=2000-01-02')->assertJsonPath('data.total', 0);
        $this->getJson('/api/maintenances?vehicle=daily:'.$this->car->id)->assertJsonPath('meta.total', 3);
        $record = VehicleMaintenance::first();
        $this->putJson('/api/maintenances/'.$record->id, $this->data(['status' => 'Cancelled']))->assertOk();
        $this->deleteJson('/api/maintenances/'.$record->id)->assertNoContent();
        $this->getJson('/api/maintenances/options')->assertOk()->assertJsonCount(1, 'data.vehicles');
    }

    public function test_manual_maintenance_restriction_is_retained_after_work_completes(): void
    {
        $this->car->update(['availability_status' => 'Maintenance']);
        $this->actingAs($this->user);
        $id = $this->postJson('/api/maintenances', $this->data(['status' => 'In Progress']))->assertCreated()->json('data.id');
        $this->putJson('/api/maintenances/'.$id, $this->data(['status' => 'Completed']))->assertOk();
        $this->getJson('/api/cars/'.$this->car->id)->assertJsonPath('data.effective_status', 'Maintenance');
        $this->assertSame('Maintenance', $this->car->fresh()->availability_status);
    }
}

