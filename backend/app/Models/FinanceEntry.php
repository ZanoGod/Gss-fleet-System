<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceEntry extends Model
{
    protected $fillable = ['type', 'category', 'description', 'amount', 'currency', 'entry_date', 'status', 'payment_method', 'reference', 'customer_id', 'daily_booking_id', 'monthly_contract_id', 'vehicle_fleet', 'vehicle_id', 'maintenance_id', 'insurance_policy_id', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'entry_date' => 'date:Y-m-d'];
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function dailyBooking() { return $this->belongsTo(DailyBooking::class, 'daily_booking_id'); }
    public function monthlyContract() { return $this->belongsTo(MonthlyContract::class, 'monthly_contract_id'); }
    public function maintenance() { return $this->belongsTo(VehicleMaintenance::class, 'maintenance_id'); }
    public function insurancePolicy() { return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id'); }
    public function invoice() { return $this->hasOne(FinanceInvoice::class, 'income_entry_id'); }
}
