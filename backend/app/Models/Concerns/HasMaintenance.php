<?php

namespace App\Models\Concerns;

use App\Models\MonthlyCar;
use App\Models\VehicleMaintenance;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasMaintenance
{
    public function maintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class, $this instanceof MonthlyCar ? 'monthly_car_id' : 'car_id');
    }

    /** Maintenance is a restriction, never an overwrite of manually managed legacy status. */
    public function effectiveAvailability(): string
    {
        if ($this->maintenances()->where('status', 'In Progress')->exists()) return 'Maintenance';
        if ($this->availability_status !== 'Available') return $this->availability_status;
        $today = now()->toDateString();
        $active = fn ($q) => $q->whereIn('status', $this instanceof MonthlyCar ? ['Draft', 'Active'] : ['Pending', 'Confirm'])->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->exists();
        if ($this instanceof MonthlyCar) return $active($this->contracts()) ? 'Assigned' : 'Available';
        return $active($this->bookings()) || $active($this->secondaryBookings()) ? 'Assigned' : 'Available';
    }
}
