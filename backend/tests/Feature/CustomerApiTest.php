<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MonthlyContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $this->actingAs(User::factory()->create(), 'web');
    }

    private function customer(array $values = []): Customer
    {
        return Customer::create(array_merge([
            'company_name' => 'Example Company',
            'service_hour' => '09:00 AM - 06:00 PM',
            'customer_status' => 'Inactive',
        ], $values));
    }

    private function contract(Customer $customer, array $values = []): MonthlyContract
    {
        static $number = 0;
        return MonthlyContract::create(array_merge([
            'customer_id' => $customer->id,
            'contract_no' => 'TEST-'.(++$number),
            'customer_name' => $customer->company_name,
            'assignment_type' => 'Vehicle Only',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'monthly_rate' => '1000',
            'rental_currency' => 'MMK',
            'rental_status' => 'Car only',
            'status' => 'Draft',
        ], $values));
    }

    public function test_all_customer_actions_require_authentication(): void
    {
        $customer = $this->customer();
        foreach (['/api/customers', '/api/customers/summary', '/api/customers/'.$customer->id] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
        $this->postJson('/api/customers', [])->assertUnauthorized();
        $this->patchJson('/api/customers/'.$customer->id, [])->assertUnauthorized();
        $this->deleteJson('/api/customers/'.$customer->id)->assertUnauthorized();
    }

    public function test_create_edit_and_delete_preserve_status_and_fields(): void
    {
        $this->signIn();
        $id = $this->postJson('/api/customers', [
            'company_name' => '  New Company  ', 'service_hour' => '10 hours', 'contact_phone' => '09-123',
        ])->assertCreated()->assertJsonPath('data.company_name', 'New Company')
            ->assertJsonPath('data.customer_status', 'Inactive')->json('data.id');
        $this->patchJson('/api/customers/'.$id, [
            'company_name' => 'Renamed Company', 'service_hour' => '9 hours',
        ])->assertOk()->assertJsonPath('data.customer_status', 'Inactive');
        Customer::findOrFail($id)->update(['customer_status' => 'Active']);
        $this->patchJson('/api/customers/'.$id, [
            'company_name' => 'Renamed Company', 'service_hour' => '8 hours',
        ])->assertOk()->assertJsonPath('data.customer_status', 'Active');
        $this->deleteJson('/api/customers/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('customers', ['id' => $id]);
    }

    public function test_validation_and_duplicate_edit_exclusion(): void
    {
        $this->signIn();
        $customer = $this->customer();
        $this->postJson('/api/customers', [])->assertUnprocessable()->assertJsonValidationErrors(['company_name', 'service_hour']);
        $this->postJson('/api/customers', [
            'company_name' => 'Example Company', 'service_hour' => '8 hours',
        ])->assertUnprocessable()->assertJsonValidationErrors('company_name');
        $this->patchJson('/api/customers/'.$customer->id, [
            'company_name' => 'Example Company', 'service_hour' => '8 hours',
        ])->assertOk();
        $this->postJson('/api/customers', [
            'company_name' => 'Different', 'service_hour' => '8 hours',
            'contact_email' => 'invalid', 'contact_phone' => str_repeat('1', 51), 'customer_status' => 'Active',
        ])->assertUnprocessable()->assertJsonValidationErrors(['contact_email', 'contact_phone', 'customer_status']);
    }

    public function test_any_contract_blocks_delete_and_snapshots_are_preserved(): void
    {
        $this->signIn();
        $customer = $this->customer();
        $contract = $this->contract($customer, ['status' => 'Cancelled']);
        $this->deleteJson('/api/customers/'.$customer->id)->assertStatus(409)
            ->assertJsonPath('code', 'CUSTOMER_HAS_CONTRACTS');
        $this->patchJson('/api/customers/'.$customer->id, [
            'company_name' => 'Changed', 'service_hour' => '8 hours',
        ])->assertOk();
        $this->assertSame('Example Company', $contract->fresh()->customer_name);
        $this->assertDatabaseHas('monthly_contracts', ['id' => $contract->id, 'customer_id' => $customer->id]);
    }

    public function test_list_has_ten_rows_per_page_alphabetical_order_and_global_summary(): void
    {
        $this->signIn();
        for ($i = 0; $i < 12; $i++) {
            $this->customer(['company_name' => sprintf('Company %02d', $i)]);
        }
        $this->getJson('/api/customers')->assertOk()->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12)->assertJsonPath('data.0.company_name', 'Company 00');
        $this->getJson('/api/customers?page=2')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/customers/summary?search=no-match')->assertOk()->assertJsonPath('data.total', 12);
    }

    public function test_search_filters_and_month_scope_preserve_legacy_semantics(): void
    {
        $this->signIn();
        $this->travelTo(now()->setDate(2026, 10, 30)->startOfDay());
        $customer = $this->customer();
        $this->contract($customer, ['contract_no' => 'FIND-ME', 'assignment_type' => 'Driver Only', 'rental_status' => 'Driver only']);
        $this->contract($customer, ['rental_status' => 'Including fuel', 'status' => 'Active']);
        $this->contract($customer, ['status' => 'Cancelled']);
        $this->getJson('/api/customers?search=FIND-ME')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/customers?assignment_type=Driver%20Only&rental_status=Including%20fuel&expiry=expiring')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/customers?status=Active')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/customers/'.$customer->id.'?month=2026-11')->assertOk()
            ->assertJsonCount(2, 'data.contracts')->assertJsonPath('data.contract_count', 3)
            ->assertJsonPath('data.active_contract_count', 1)->assertJsonPath('meta.month', '2026-11');
        $this->getJson('/api/customers/'.$customer->id.'?month=2027-01')->assertOk()
            ->assertJsonCount(0, 'data.contracts')->assertJsonPath('data.contract_count', 3);
        $this->getJson('/api/customers?month=2026-13')->assertUnprocessable();
        $this->getJson('/api/customers/999999')->assertNotFound();
        $this->travelBack();
    }
}
