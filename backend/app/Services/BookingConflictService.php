<?php

namespace App\Services;

use App\Models\Car;
use App\Models\DailyBooking;
use App\Models\Driver;
use Illuminate\Validation\ValidationException;

class BookingConflictService
{
    /** Locks each selected resource in a stable order and validates legacy inclusive DATE overlaps. */
    public function lockAndValidate(array $data, ?int $exceptBookingId = null): void
    {
        $carIds = collect([$data['car_id'] ?? null, $data['secondary_car_id'] ?? null])->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values();
        foreach ($carIds as $carId) {
            $car = Car::query()->lockForUpdate()->findOrFail($carId);
            if ($car->availability_status === 'Maintenance') {
                throw ValidationException::withMessages(['car_id' => "Vehicle {$car->plate_no} is under maintenance."]);
            }
        }

        $driverId = $data['driver_id'] ?? null;
        if ($driverId) {
            $driver = Driver::query()->lockForUpdate()->findOrFail($driverId);
            if (in_array($driver->driver_status, ['Leave', 'Inactive'], true)) {
                throw ValidationException::withMessages(['driver_id' => 'A driver on leave or inactive cannot be assigned.']);
            }
        }

        if (($data['status'] ?? null) !== 'Confirm') {
            return;
        }
        if ($carIds->isEmpty() && ! $driverId) {
            return;
        }

        $overlap = DailyBooking::query()->whereIn('status', ['Pending', 'Confirm'])
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->when($exceptBookingId, fn ($query) => $query->where('id', '!=', $exceptBookingId))
            ->where(function ($query) use ($carIds, $driverId): void {
                if ($carIds->isNotEmpty()) {
                    $query->whereIn('car_id', $carIds)->orWhereIn('secondary_car_id', $carIds);
                }
                if ($driverId) {
                    $method = $carIds->isNotEmpty() ? 'orWhere' : 'where';
                    $query->{$method}('driver_id', $driverId);
                }
            })->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'This confirmed booking overlaps an existing pending or confirmed booking for the selected vehicle or driver.']);
        }
    }
}
