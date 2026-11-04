<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\DailyBooking;
use App\Models\Driver;
use App\Models\MonthlyContract;
use App\Models\User;
use App\Models\VehicleMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_session_and_resource_permissions(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        Gate::before(fn () => false);
        $this->getJson('/api/dashboard')->assertForbidden();
    }

    public function test_empty_dashboard_reports_real_zeroes(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('data.total_bookings', 0)->assertJsonPath('data.available_cars', 0)
            ->assertJsonPath('data.available_drivers', 0)->assertJsonPath('data.ongoing_bookings', 0)
            ->assertJsonPath('data.expiring_contracts', 0)->assertJsonCount(0, 'data.recent_bookings');
    }

    public function test_metrics_use_dates_statuses_reservations_and_maintenance_without_pagination(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->startOfDay());
        $this->actingAs(User::factory()->create());
        $cars = collect(range(1, 6))->map(fn ($i) => Car::create([
            'car_type' => 'Van', 'plate_no' => 'DASH-'.$i, 'model_name' => 'Van',
            'seat_capacity' => 8, 'availability_status' => $i === 6 ? 'Assigned' : 'Available',
        ]));
        $drivers = collect(range(1, 4))->map(fn ($i) => Driver::create([
            'full_name' => 'Driver '.$i, 'phone_number' => '09123456',
            'driver_status' => $i === 4 ? 'Leave' : 'Available',
        ]));
        VehicleMaintenance::create(['car_id' => $cars[2]->id, 'maintenance_type' => 'Preventive',
            'title' => 'Service', 'scheduled_date' => '2026-11-03', 'status' => 'In Progress', 'currency' => 'MMK']);
        foreach (['Confirm', 'Pending', 'Completed', 'Cancelled', 'Confirm', 'Confirm'] as $i => $status) {
            DailyBooking::create([
                'guest_company_name' => 'Guest '.$i, 'operator_name' => 'Operator',
                'car_id' => $i === 0 ? $cars[0]->id : null,
                'secondary_car_id' => $i === 0 ? $cars[1]->id : null,
                'driver_id' => $i < 2 ? $drivers[$i]->id : null,
                'status' => $status,
                'start_date' => $i === 4 ? '2026-11-04' : '2026-11-01',
                'end_date' => $i === 5 ? '2026-11-02' : '2026-11-10',
            ]);
        }
        foreach ([['Active', '2026-11-03'], ['Active', '2026-12-03'], ['Active', '2026-12-04'],
            ['Active', '2026-11-02'], ['Draft', '2026-11-10'], ['Cancelled', '2026-11-10'],
            ['Renewed', '2026-11-10'], ['Expired', '2026-11-03']] as $i => [$status, $end]) {
            MonthlyContract::create(['contract_no' => 'DASH-'.$i, 'customer_name' => 'Customer',
                'assignment_type' => 'Driver Only', 'start_date' => '2026-11-01', 'end_date' => $end,
                'monthly_rate' => 100, 'rental_currency' => 'MMK', 'rental_status' => 'Driver only', 'status' => $status]);
        }
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.total_bookings', 6)
            ->assertJsonPath('data.available_cars', 2)->assertJsonPath('data.available_drivers', 1)
            ->assertJsonPath('data.ongoing_bookings', 1)->assertJsonPath('data.expiring_contracts', 2)
            ->assertJsonCount(5, 'data.recent_bookings')->assertJsonPath('data.recent_bookings.0.guest_company_name', 'Guest 5');
    }
}
