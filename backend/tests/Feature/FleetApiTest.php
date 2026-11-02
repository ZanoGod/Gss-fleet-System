<?php

namespace Tests\Feature;

use App\Models\{Car, DailyBooking, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetApiTest extends TestCase
{
    use RefreshDatabase;
    private function data(array $extra = []): array { return array_merge(['car_type' => 'Van', 'plate_no' => 'GSS-101', 'model_name' => 'Hiace', 'seat_capacity' => 8, 'availability_status' => 'Available'], $extra); }
    public function test_authentication_crud_validation_and_scope(): void
    {
        $this->getJson('/api/cars')->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $id = $this->postJson('/api/cars', $this->data())->assertCreated()->json('data.id');
        $this->postJson('/api/cars', $this->data())->assertUnprocessable()->assertJsonValidationErrors('plate_no');
        $this->postJson('/api/cars?resource_type=monthly', $this->data(['availability_status' => 'Inactive']))->assertCreated()->assertJsonPath('data.resource_type', 'monthly');
        $this->putJson("/api/cars/{$id}", $this->data(['seat_capacity' => 0]))->assertUnprocessable();
        $this->putJson("/api/cars/{$id}", $this->data(['model_name' => 'Updated']))->assertOk();
        $this->getJson('/api/cars?search=Updated&per_page=1')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/cars/summary')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson("/api/cars/{$id}")->assertOk();
        $this->deleteJson("/api/cars/{$id}")->assertNoContent();
    }
    public function test_historical_and_secondary_assignments_are_visible_and_protected(): void
    {
        $car = Car::create($this->data());
        DailyBooking::create(['guest_company_name' => 'Historic Guest', 'secondary_car_id' => $car->id, 'operator_name' => 'Snapshot', 'start_date' => '2025-01-31', 'end_date' => '2025-02-01', 'status' => 'Completed']);
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/cars?month=2025-02&search=Historic')->assertOk()->assertJsonPath('data.0.assignments.0.name', 'Historic Guest');
        $this->getJson('/api/cars?month=2025-03')->assertOk()->assertJsonCount(0, 'data.0.assignments');
        $this->deleteJson('/api/cars/'.$car->id)->assertUnprocessable();
        $this->assertDatabaseHas('bookings', ['secondary_car_id' => $car->id]);
    }
}
