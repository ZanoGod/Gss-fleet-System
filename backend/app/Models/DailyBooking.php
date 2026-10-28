<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyBooking extends Model
{
    public const STATUSES = ['Pending', 'Confirm', 'Completed', 'Cancelled'];

    protected $table = 'bookings';

    protected $fillable = [
        'guest_company_name', 'car_id', 'custom_car_name', 'secondary_car_id',
        'driver_id', 'custom_driver_name', 'operator_id', 'operator_name', 'even_odd',
        'start_date', 'end_date', 'status', 'remark',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d'];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function secondaryCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'secondary_car_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }
}
