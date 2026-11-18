<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceInvoice extends Model
{
    protected $table = 'finance_invoices';
    protected $appends = ['paid_amount', 'balance', 'payment_status'];
    protected $fillable = ['invoice_number', 'income_entry_id', 'customer_id', 'daily_booking_id', 'monthly_contract_id', 'description', 'issue_date', 'due_date', 'subtotal', 'tax_amount', 'total', 'currency', 'status', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['issue_date' => 'date:Y-m-d', 'due_date' => 'date:Y-m-d', 'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function incomeEntry() { return $this->belongsTo(FinanceEntry::class, 'income_entry_id'); }
    public function payments() { return $this->hasMany(FinancePayment::class, 'invoice_id'); }
    public function getPaidAmountAttribute(): string { return number_format((float) $this->payments_sum_amount, 2, '.', ''); }
    public function getBalanceAttribute(): string { return number_format(max(0, (float) $this->total - (float) $this->payments_sum_amount), 2, '.', ''); }
    public function getPaymentStatusAttribute(): string
    {
        if ($this->status === 'void') return 'Void';
        if ($this->status === 'draft') return 'Draft';
        if ((float) $this->payments_sum_amount >= (float) $this->total) return 'Paid';
        if ((float) $this->payments_sum_amount > 0) return 'Partially paid';
        return $this->due_date->lt(today()) ? 'Overdue' : 'Unpaid';
    }
}
