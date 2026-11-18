<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FinanceEntry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role_id' => Role::where('slug', 'administrator')->value('id')]);
        $this->actingAs($this->admin, 'web');
    }

    public function test_income_expense_invoice_and_payment_workflow_calculates_balances(): void
    {
        $customer = Customer::create(['company_name' => 'GSS Client', 'customer_status' => 'Active']);
        $expenseId = $this->postJson('/api/finance/entries', [
            'type' => 'expense', 'category' => 'Fuel', 'description' => 'Fuel stop', 'amount' => '25.50',
            'currency' => 'MMK', 'entry_date' => today()->toDateString(), 'status' => 'posted', 'customer_id' => $customer->id,
        ])->assertCreated()->assertJsonPath('data.amount', '25.50')->json('data.id');
        $this->putJson("/api/finance/entries/{$expenseId}", [
            'type' => 'expense', 'category' => 'Fuel', 'description' => 'Fuel refill', 'amount' => '25.50',
            'currency' => 'MMK', 'entry_date' => today()->toDateString(), 'status' => 'posted', 'customer_id' => $customer->id,
        ])->assertOk()->assertJsonPath('data.description', 'Fuel refill');

        $invoiceId = $this->postJson('/api/finance/invoices', [
            'customer_id' => $customer->id, 'description' => 'Daily rental', 'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(10)->toDateString(), 'subtotal' => '100.00', 'tax_amount' => '5.00',
            'total' => '105.00', 'currency' => 'MMK', 'status' => 'sent',
        ])->assertCreated()->assertJsonPath('data.total', '105.00')->json('data.id');

        $this->postJson("/api/finance/invoices/{$invoiceId}/payments", [
            'payment_date' => today()->toDateString(), 'amount' => '50.00', 'payment_method' => 'Cash',
        ])->assertCreated();
        $this->getJson('/api/finance/invoices')->assertOk()
            ->assertJsonPath('data.0.payment_status', 'Partially paid')
            ->assertJsonPath('data.0.balance', '55.00');

        $this->postJson("/api/finance/invoices/{$invoiceId}/payments", [
            'payment_date' => today()->toDateString(), 'amount' => '60.00', 'payment_method' => 'Cash',
        ])->assertUnprocessable()->assertJsonValidationErrors('amount');

        $paymentId = $this->getJson("/api/finance/invoices/{$invoiceId}/payments")->json('data.0.id');
        $this->putJson("/api/finance/invoices/{$invoiceId}/payments/{$paymentId}", [
            'payment_date' => today()->toDateString(), 'amount' => '105.00', 'payment_method' => 'Bank transfer',
        ])->assertOk();
        $this->getJson('/api/finance/invoices')->assertJsonPath('data.0.payment_status', 'Paid')->assertJsonPath('data.0.balance', '0.00');
        $this->deleteJson("/api/finance/invoices/{$invoiceId}")->assertUnprocessable();

        $this->getJson('/api/finance/dashboard')->assertOk()->assertJsonPath('data.totals.0.income', '100.00');
        $this->getJson('/api/finance/report')->assertOk()->assertJsonStructure(['data' => ['by_category', 'by_month', 'by_vehicle']]);
        $this->get('/api/finance/export.csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertSame(1, FinanceEntry::where('type', 'income')->count());
        $this->deleteJson("/api/finance/invoices/{$invoiceId}/payments/{$paymentId}")->assertNoContent();
        $this->deleteJson("/api/finance/invoices/{$invoiceId}")->assertNoContent();
        $this->deleteJson("/api/finance/entries/{$expenseId}")->assertNoContent();
        $this->assertSame(0, FinanceEntry::where('type', 'income')->count());
    }

    public function test_finance_reader_can_view_but_cannot_manage_records(): void
    {
        $readerRole = Role::create(['name' => 'Finance reader', 'slug' => 'finance-reader', 'permissions' => ['finance.view']]);
        $reader = User::factory()->create(['role_id' => $readerRole->id]);
        $this->actingAs($reader, 'web')->getJson('/api/finance/dashboard')->assertOk();
        $this->postJson('/api/finance/entries', [])->assertForbidden();
    }
}
