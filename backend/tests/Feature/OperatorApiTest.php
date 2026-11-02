<?php
namespace Tests\Feature;

use App\Models\{Operator, DailyBooking, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_monthly_snapshots_and_inactive_existing_daily_assignments_are_preserved(): void
    {
        $operator = Operator::create(['full_name' => 'Original', 'operator_status' => 'Active']);
        $contract = \App\Models\MonthlyContract::create(['contract_no' => 'MC-OP', 'customer_name' => 'Company', 'assignment_type' => 'Driver Only', 'operator_id' => $operator->id, 'operator_name' => 'Historical monthly name', 'start_date' => '2025-01-01', 'end_date' => '2025-01-31', 'monthly_rate' => 0, 'rental_currency' => 'MMK', 'rental_status' => 'Driver only', 'status' => 'Expired']);
        $this->actingAs(User::factory()->create());
        $this->putJson('/api/operators/'.$operator->id, ['full_name' => 'Renamed', 'operator_status' => 'Inactive'])->assertOk();
        $this->assertSame('Historical monthly name', $contract->fresh()->operator_name);
        $this->deleteJson('/api/operators/'.$operator->id)->assertUnprocessable();
        $booking = DailyBooking::create(['guest_company_name' => 'History', 'operator_id' => $operator->id, 'operator_name' => 'Renamed', 'start_date' => '2025-01-01', 'end_date' => '2025-01-02', 'even_odd' => 'Even', 'status' => 'Completed']);
        $data = $booking->only(['guest_company_name', 'operator_id', 'even_odd', 'status']);
        $data += ['start_date' => '2025-01-01', 'end_date' => '2025-01-02', 'remark' => 'Historical correction'];
        $this->putJson('/api/daily-bookings/'.$booking->id, $data)->assertOk();
        $this->postJson('/api/daily-bookings', $data)->assertUnprocessable()->assertJsonValidationErrors('operator_id');
    }
    public function test_crud_filters_validation_and_legacy_daily_name_sync(): void
    {
        $this->getJson('/api/operators')->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $data = ['full_name' => 'Legacy Operator', 'phone_number' => '091234', 'operator_status' => 'Active', 'note' => 'Dispatch'];
        $id = $this->postJson('/api/operators', $data)->assertCreated()->json('data.id');
        $this->postJson('/api/operators', $data)->assertUnprocessable();
        $this->getJson('/api/operators?search=Dispatch&status=Active&per_page=1')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/operators/summary')->assertJsonPath('data.active', 1);
        $booking = DailyBooking::create(['guest_company_name' => 'History', 'operator_id' => $id, 'operator_name' => 'Legacy Operator', 'start_date' => '2025-01-01', 'end_date' => '2025-01-02', 'status' => 'Completed']);
        $this->putJson('/api/operators/'.$id, array_merge($data, ['full_name' => 'Updated Operator', 'operator_status' => 'Inactive']))->assertOk();
        $this->assertSame('Updated Operator', $booking->fresh()->operator_name);
        $this->getJson('/api/operators/'.$id)->assertJsonPath('data.assignments.0.name', 'History');
        $this->deleteJson('/api/operators/'.$id)->assertUnprocessable();
        $unused = Operator::create(['full_name' => 'Unused', 'operator_status' => 'Active']);
        $this->deleteJson('/api/operators/'.$unused->id)->assertNoContent();
    }
}
