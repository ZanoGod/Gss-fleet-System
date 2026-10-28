<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operator extends Model
{
    public const STATUSES = ['Active', 'Inactive'];

    protected $table = 'operators';

    protected $fillable = ['full_name', 'phone_number', 'operator_status', 'note'];

    public function bookings(): HasMany
    {
        return $this->hasMany(DailyBooking::class, 'operator_id');
    }

    public function monthlyContracts(): HasMany
    {
        return $this->hasMany(MonthlyContract::class, 'operator_id');
    }
}
