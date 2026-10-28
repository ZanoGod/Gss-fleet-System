<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    public const STATUSES = ['Available', 'Assigned', 'Maintenance'];

    protected $table = 'cars';

    protected $fillable = ['car_type', 'plate_no', 'model_name', 'seat_capacity', 'availability_status', 'note'];

    protected function casts(): array
    {
        return ['seat_capacity' => 'integer'];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(DailyBooking::class, 'car_id');
    }

    public function secondaryBookings(): HasMany
    {
        return $this->hasMany(DailyBooking::class, 'secondary_car_id');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'car_id');
    }
}
