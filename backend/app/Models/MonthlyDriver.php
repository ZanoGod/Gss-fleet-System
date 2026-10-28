<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyDriver extends Model
{
    public const STATUSES = ['Available', 'Assigned', 'Leave', 'Inactive'];

    protected $table = 'monthly_drivers';

    protected $fillable = ['full_name', 'phone_number', 'license_no', 'driver_status', 'note'];

    public function contracts(): HasMany
    {
        return $this->hasMany(MonthlyContract::class, 'driver_id');
    }
}
