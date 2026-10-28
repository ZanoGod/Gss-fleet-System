<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyCar extends Model
{
    public const STATUSES = ['Available', 'Assigned', 'Maintenance', 'Inactive'];

    protected $table = 'monthly_cars';

    protected $fillable = ['car_type', 'plate_no', 'model_name', 'seat_capacity', 'availability_status', 'note'];

    protected function casts(): array
    {
        return ['seat_capacity' => 'integer'];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(MonthlyContract::class, 'car_id');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'monthly_car_id');
    }
}
