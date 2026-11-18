<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancePayment extends Model
{
    protected $table = 'finance_payments';
    protected $fillable = ['invoice_id', 'payment_date', 'amount', 'currency', 'payment_method', 'reference', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['payment_date' => 'date:Y-m-d', 'amount' => 'decimal:2'];
    }

    public function invoice() { return $this->belongsTo(FinanceInvoice::class, 'invoice_id'); }
}
