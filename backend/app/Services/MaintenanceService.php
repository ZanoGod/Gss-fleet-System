<?php

namespace App\Services;

use App\Models\Car;
use App\Models\DailyBooking;
use App\Models\MonthlyCar;
use App\Models\MonthlyContract;
use App\Models\User;
use App\Models\VehicleMaintenance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceService
{
    public static function assertAssignable($vehicle): void
    {
        if ($vehicle->maintenances()->where('status', 'In Progress')->exists()) {
            throw ValidationException::withMessages(['car_id' => 'This vehicle has maintenance in progress and cannot be assigned.']);
        }
    }

    public function save(array $data, User $user, ?VehicleMaintenance $existing = null): VehicleMaintenance
    {
        return DB::transaction(function () use ($data, $user, $existing) {
            $record = $existing ? VehicleMaintenance::query()->lockForUpdate()->findOrFail($existing->id) : new VehicleMaintenance;
            $carId = $data['car_id'] ?? null;
            $monthlyId = $data['monthly_car_id'] ?? null;
            if ((bool) $carId === (bool) $monthlyId) {
                throw ValidationException::withMessages(['car_id' => 'Select exactly one daily or monthly vehicle.']);
            }
            $vehicle = $carId ? Car::query()->lockForUpdate()->findOrFail($carId) : MonthlyCar::query()->lockForUpdate()->findOrFail($monthlyId);
            $status = $data['status'];
            $allowed = ['Scheduled' => ['Scheduled', 'In Progress', 'Cancelled'], 'In Progress' => ['In Progress', 'Completed', 'Cancelled'], 'Completed' => ['Completed'], 'Cancelled' => ['Cancelled']];
            if ((! $record->exists && ! in_array($status, ['Scheduled', 'In Progress'], true)) || ($record->exists && ! in_array($status, $allowed[$record->status], true))) {
                throw ValidationException::withMessages(['status' => 'Invalid maintenance transition. Start scheduled work before completing it. Closed work cannot be reopened.']);
            }
            if ($record->exists && ($record->started_at || in_array($record->status, ['Completed', 'Cancelled'], true)) && ((int) $record->car_id !== (int) $carId || (int) $record->monthly_car_id !== (int) $monthlyId)) {
                throw ValidationException::withMessages(['car_id' => 'The vehicle cannot change after work has started or closed.']);
            }

            if ($status === 'Scheduled' && (! empty($data['started_at']) || ! empty($data['completed_at']))) {
                throw ValidationException::withMessages(['started_at' => 'Scheduled work cannot have work dates.']);
            }
            if ($status === 'In Progress') {
                $data['started_at'] = $record->started_at?->format('Y-m-d') ?? ($data['started_at'] ?? now()->toDateString());
                if (! empty($data['completed_at'])) {
                    throw ValidationException::withMessages(['completed_at' => 'Complete the work order before setting a completion date.']);
                }
                if ($vehicle->availability_status === 'Assigned') {
                    throw ValidationException::withMessages(['car_id' => 'Resolve the vehicle’s stored assignment restriction before starting maintenance.']);
                }
                $others = $vehicle->maintenances()->where('status', 'In Progress')->when($record->exists, fn ($q) => $q->where('id', '!=', $record->id))->exists();
                if ($others) {
                    throw ValidationException::withMessages(['car_id' => 'Another maintenance work order is already in progress.']);
                }
                // Work has no promised end date: reject every present/future assignment that would be interrupted.
                $assignments = $carId ? DailyBooking::query()->where(fn ($q) => $q->where('car_id', $carId)->orWhere('secondary_car_id', $carId))->whereIn('status', ['Pending', 'Confirm']) : MonthlyContract::query()->where('car_id', $monthlyId)->whereIn('status', ['Draft', 'Active']);
                if ($assignments->whereDate('end_date', '>=', $data['started_at'])->exists()) {
                    throw ValidationException::withMessages(['started_at' => 'This vehicle has a current or future assignment. Resolve it before starting open-ended maintenance.']);
                }
            }
            if ($status === 'Completed') {
                $data['started_at'] = $record->started_at?->format('Y-m-d');
                $data['completed_at'] = $data['completed_at'] ?? $record->completed_at?->format('Y-m-d') ?? now()->toDateString();
                if ($data['completed_at'] < $data['started_at']) {
                    throw ValidationException::withMessages(['completed_at' => 'Completion cannot precede the start date.']);
                }
            }
            if ($status === 'Cancelled') {
                $data['started_at'] = $record->started_at?->format('Y-m-d');
                $data['completed_at'] = null;
            }
            $record->fill($data);
            if (! $record->exists) {
                $record->created_by = $user->id;
            }
            $record->save();

            return $record->load('car', 'monthlyCar');
        }, 3);
    }

    public function delete(VehicleMaintenance $maintenance): void
    {
        DB::transaction(function () use ($maintenance) {
            $record = VehicleMaintenance::query()->lockForUpdate()->findOrFail($maintenance->id);
            if ($record->started_at || ! in_array($record->status, ['Scheduled', 'Cancelled'], true)) {
                throw ValidationException::withMessages(['maintenance' => 'Started work and completed service history cannot be deleted. Cancel active work instead.']);
            }
            $record->delete();
        }, 3);
    }
}
