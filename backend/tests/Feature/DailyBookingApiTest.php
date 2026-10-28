<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\DailyBooking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyBookingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Car $car;
    private Car $secondCar;
    private Driver $driver;
    private Operator $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->car = Car::create(['car_type' => 'Van', 'plate_no' => 'BOOK-1', 'model_name' => 'One', 'seat_capacity' => 8, 'availability_status' => 'Available']);
        $this->secondCar = Car::create(['car_type' => 'Van', 'plate_no' => 'BOOK-2', 'model_name' => 'Two', 'seat_capacity' => 8, 'availability_status' => 'Available']);
        $this->driver = Driver::create(['full_name' => 'Driver One', 'phone_number' => '09-123', 'driver_status' => 'Available']);
        $this->operator = Operator::create(['full_name' => 'Operator One', 'operator_status' => 'Active']);
    }

    private function bookingData(array $overrides = []): array
    {
        return array_merge([
            'guest_company_name' => 'Example Guest', 'car_id' => $this->car->id,
            'secondary_car_id' => null, 'custom_car_name' => null,
            'driver_id' => $this->driver->id, 'custom_driver_name' => null,
            'operator_id' => $this->operator->id, 'even_odd' => 'Even',
            'start_date' => '2026-11-10', 'end_date' => '2026-11-12',
            'status' => 'Confirm', 'remark' => null,
        ], $overrides);
    }

    public function test_booking_is_created_with_operator_name_snapshot_and_no_calculated_charges(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData());
        $response->assertCreated()->assertJsonPath('data.operator_name', 'Operator One')
            ->assertJsonPath('data.status', 'Confirm')->assertJsonMissingPath('data.charges');
        $this->assertDatabaseHas('bookings', ['id' => $response->json('data.id'), 'operator_name' => 'Operator One']);
    }

    public function test_confirm_checks_inclusive_overlap_in_both_vehicle_slots_and_driver(): void
    {
        $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData(['status' => 'Pending', 'secondary_car_id' => $this->secondCar->id]))->assertCreated();
        $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData([
            'car_id' => null, 'secondary_car_id' => $this->secondCar->id, 'driver_id' => null,
            'start_date' => '2026-11-12', 'end_date' => '2026-11-13',
        ]))->assertUnprocessable()->assertJsonValidationErrors('start_date');

        $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData([
            'car_id' => null, 'secondary_car_id' => null, 'custom_car_name' => 'Outside fleet',
            'start_date' => '2026-11-12', 'end_date' => '2026-11-13',
        ]))->assertUnprocessable()->assertJsonValidationErrors('start_date');
    }

    public function test_pending_can_overlap_and_update_excludes_its_own_booking(): void
    {
        $first = $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData(['status' => 'Pending']))->assertCreated()->json('data.id');
        $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData(['status' => 'Pending']))->assertCreated();
        $this->actingAs($this->user)->putJson("/api/daily-bookings/{$first}", $this->bookingData(['status' => 'Completed']))->assertOk();
    }

    public function test_date_validation_status_validation_and_delete_follow_legacy_values(): void
    {
        $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData(['status' => 'Confirmed']))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $created = $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData(['end_date' => '2026-11-09']))
            ->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $id = $this->actingAs($this->user)->postJson('/api/daily-bookings', $this->bookingData())->assertCreated()->json('data.id');
        $this->actingAs($this->user)->deleteJson("/api/daily-bookings/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('bookings', ['id' => $id]);
    }
}
