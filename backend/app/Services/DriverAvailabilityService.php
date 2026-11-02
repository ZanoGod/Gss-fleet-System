<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\MonthlyDriver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DriverAvailabilityService
{
    public function model(string $scope): string
    {
        return $scope === 'monthly' ? MonthlyDriver::class : Driver::class;
    }

    /** Respect the installed enum, including older daily schemas using On Trip. No data rewriting. */
    public function statuses(string $scope): array
    {
        $model = $this->model($scope);
        $column = collect(Schema::getColumns((new $model)->getTable()))->firstWhere('name', 'driver_status');
        $type = $column['type'] ?? '';
        if (str_starts_with(strtolower($type), 'enum(') && preg_match_all("/'([^']+)'/", $type, $matches)) {
            return $matches[1];
        }

        return $model::STATUSES;
    }

    public function assignments(Driver|MonthlyDriver $driver)
    {
        return $driver instanceof MonthlyDriver ? $driver->contracts() : $driver->bookings();
    }

    public function overlaps(Driver|MonthlyDriver $driver, string $start, string $end, ?int $exceptId = null): bool
    {
        return $this->assignments($driver)->whereIn('status', $driver instanceof MonthlyDriver ? ['Draft', 'Active'] : ['Pending', 'Confirm'])
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists();
    }

    public function available(Driver|MonthlyDriver $driver, string $start, string $end, ?int $exceptId = null): bool
    {
        // Assigned/On Trip are manual restrictions on NEW selections; current bookings can retain their driver.
        return $driver->driver_status === 'Available' && ! $this->overlaps($driver, $start, $end, $exceptId);
    }

    public function assertStoredStatus(Driver|MonthlyDriver $driver, bool $retained, bool $historical): void
    {
        if ($historical && $retained) {
            return;
        }
        if (in_array($driver->driver_status, ['Leave', 'Inactive'], true) || (! $retained && $driver->driver_status !== 'Available')) {
            throw ValidationException::withMessages(['driver_id' => 'Choose an Available driver. A driver on leave or inactive cannot receive an active assignment.']);
        }
    }
}
