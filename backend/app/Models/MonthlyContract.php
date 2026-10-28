<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyContract extends Model
{
    public const ASSIGNMENT_TYPES = ['Driver Only', 'Vehicle Only', 'Vehicle and Driver'];
    public const STATUSES = ['Draft', 'Active', 'Expired', 'Renewed', 'Cancelled'];
    public const CURRENCIES = ['MMK', 'USD'];
    public const RENTAL_STATUSES = ['Including fuel', 'Excluding fuel', 'Car only', 'Driver only'];

    protected $table = 'monthly_contracts';

    protected $fillable = [
        'contract_no', 'customer_id', 'customer_name', 'guest_name', 'contact_person',
        'customer_phone', 'customer_email', 'customer_address', 'assignment_type', 'car_id',
        'driver_id', 'operator_id', 'operator_name', 'even_odd', 'operation_sheet',
        'start_date', 'end_date', 'monthly_rate', 'rental_currency', 'rental_status',
        'status', 'renewal_parent_id', 'note', 'prepared_by', 'invoice_sender', 'checked_by',
        'off_day', 'service_hour', 'insurance_price', 'insurance_company', 'terms_conditions',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'monthly_rate' => 'decimal:2',
            'insurance_price' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(MonthlyCar::class, 'car_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(MonthlyDriver::class, 'driver_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function renewalParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewal_parent_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewal_parent_id');
    }
}
