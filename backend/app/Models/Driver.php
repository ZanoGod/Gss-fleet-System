<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    public const STATUSES = ['Available', 'Assigned', 'Leave', 'Inactive'];

    protected $table = 'drivers';

    protected $fillable = ['full_name', 'phone_number', 'license_no', 'driver_status', 'note'];

    public function bookings(): HasMany
    {
        return $this->hasMany(DailyBooking::class, 'driver_id');
    }
}
