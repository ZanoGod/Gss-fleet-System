<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleMaintenance extends Model
{
    public const TYPES = ['Preventive', 'Corrective', 'Inspection', 'Other'];

    public const STATUSES = ['Scheduled', 'In Progress', 'Completed', 'Cancelled'];

    protected $fillable = ['car_id', 'monthly_car_id', 'maintenance_type', 'title', 'description', 'scheduled_date', 'started_at', 'completed_at', 'odometer_reading', 'service_provider', 'estimated_cost', 'actual_cost', 'currency', 'status', 'notes'];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date:Y-m-d', 'started_at' => 'date:Y-m-d', 'completed_at' => 'date:Y-m-d', 'estimated_cost' => 'decimal:2', 'actual_cost' => 'decimal:2', 'odometer_reading' => 'integer'];
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function monthlyCar()
    {
        return $this->belongsTo(MonthlyCar::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
