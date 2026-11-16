<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarLicense;
use App\Models\MonthlyCar;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarLicenseService
{
    public function save(array $data, User $user, ?CarLicense $existing = null, bool $renewal = false): CarLicense
    {
        return DB::transaction(function () use ($data, $user, $existing, $renewal): CarLicense {
            $lockedParent = $existing ? CarLicense::query()->lockForUpdate()->findOrFail($existing->id) : null;
            $record = $existing && ! $renewal ? $lockedParent : new CarLicense;
            if ($renewal && (! $lockedParent || $lockedParent->renewal()->exists())) {
                throw ValidationException::withMessages(['renewal' => 'This license already has a recorded renewal.']);
            }
            if (! $renewal && $record->exists && $record->renewal()->exists()) {
                throw ValidationException::withMessages(['license' => 'A license with renewal history cannot be edited. Edit the latest renewal record instead.']);
            }
            $carId = $renewal ? $lockedParent->car_id : ($data['car_id'] ?? null);
            $monthlyId = $renewal ? $lockedParent->monthly_car_id : ($data['monthly_car_id'] ?? null);
            if ((bool) $carId === (bool) $monthlyId) {
                throw ValidationException::withMessages(['car_id' => 'Select exactly one daily or monthly vehicle.']);
            }
            $vehicle = $carId ? Car::query()->lockForUpdate()->findOrFail($carId) : MonthlyCar::query()->lockForUpdate()->findOrFail($monthlyId);
            if (! $record->exists && ! $renewal) {
                $hasLicense = CarLicense::query()->where(fn ($q) => $carId ? $q->where('car_id', $carId) : $q->where('monthly_car_id', $monthlyId))->exists();
                if ($hasLicense) throw ValidationException::withMessages(['vehicle' => 'This vehicle already has license history. Use the renewal action to record its next term.']);
            }
            $record->fill($data);
            $record->car_id = $carId;
            $record->monthly_car_id = $monthlyId;
            if ($renewal) {
                $record->renewal_parent_id = $lockedParent->id;
                $record->renewal_date = $data['renewal_date'] ?? now()->toDateString();
            }
            if (! $record->exists) {
                $record->created_by = $user->id;
            }
            $record->save();

            return $record->load('car', 'monthlyCar', 'documents');
        }, 3);
    }

    public function delete(CarLicense $license): void
    {
        DB::transaction(function () use ($license): void {
            $record = CarLicense::query()->lockForUpdate()->findOrFail($license->id);
            if ($record->renewal_parent_id || $record->renewal()->exists()) {
                throw ValidationException::withMessages(['license' => 'License records in a renewal history cannot be deleted.']);
            }
            $record->delete();
        }, 3);
    }
}
