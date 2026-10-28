<?php

namespace App\Services;

use App\Models\Car;
use App\Models\InsurancePolicy;
use App\Models\MonthlyCar;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InsurancePolicyService
{
    

    public function save(array $data, User $user, ?InsurancePolicy $existing = null, ?InsurancePolicy $parent = null): InsurancePolicy
    {
        return DB::transaction(function () use ($data, $user, $existing, $parent): InsurancePolicy {
            

            if ($existing) {
                $existing = InsurancePolicy::query()->lockForUpdate()->findOrFail($existing->id);

                if ($existing->archived_at || $existing->cancelled_at || $existing->renewal_parent_id || $existing->renewal()->exists()) {
                    throw ValidationException::withMessages(['policy' => 'Archived, cancelled, renewed, or predecessor policy terms are read-only.']);
                }
                if ($existing->car_id != ($data['car_id'] ?? null) || $existing->monthly_car_id != ($data['monthly_car_id'] ?? null)) {
                    throw ValidationException::withMessages(['car_id' => 'A policy cannot be moved to another fleet record. Archive it and create a new policy.']);
                }
                if ($existing->claims()->where(function ($query) use ($data): void {
                    $query->where('incident_date', '<', $data['start_date'])
                        ->orWhere('incident_date', '>', $data['expiration_date']);
                })->exists()) {
                    throw ValidationException::withMessages(['start_date' => 'Policy dates must continue to cover recorded incidents.']);
                }
                if ($existing->claims()->exists() && $existing->currency !== $data['currency']) {
                    throw ValidationException::withMessages(['currency' => 'Currency cannot change after claims are recorded.']);
                }
            }

            if ($parent) {
                $parent = InsurancePolicy::query()->lockForUpdate()->findOrFail($parent->id);
                if ($parent->archived_at || $parent->cancelled_at || $parent->renewal()->exists()) {
                    throw ValidationException::withMessages(['renewal' => 'This policy is inactive or already has a renewal.']);
                }
                if ($data['start_date'] <= $parent->expiration_date->format('Y-m-d')) {
                    throw ValidationException::withMessages(['start_date' => 'A renewal must start after the previous policy expires.']);
                }
                if (($data['car_id'] ?? null) != $parent->car_id
                    || ($data['monthly_car_id'] ?? null) != $parent->monthly_car_id
                    || $data['insurance_type'] !== $parent->insurance_type) {
                    throw ValidationException::withMessages(['car_id' => 'Renewals must retain the same fleet record and insurance type.']);
                }
            }

            $column = ! empty($data['car_id']) ? 'car_id' : 'monthly_car_id';
            $vehicleId = (int) $data[$column];
            $vehicleModel = $column === 'car_id' ? Car::class : MonthlyCar::class;
            $vehicleModel::query()->lockForUpdate()->findOrFail($vehicleId);

            if (config('insurance.block_same_type_overlap', true)) {
                $overlap = InsurancePolicy::query()->live()
                    ->where($column, $vehicleId)
                    ->where('insurance_type', $data['insurance_type'])
                    ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                    ->where('start_date', '<=', $data['expiration_date'])
                    ->where('expiration_date', '>=', $data['start_date'])
                    ->exists();

                if ($overlap) {
                    throw ValidationException::withMessages(['start_date' => 'This fleet record already has overlapping coverage of the same type.']);
                }
            }

            $policy = $existing ?? new InsurancePolicy();
            $policy->fill($data);
            if (! $existing) {
                $policy->created_by_user_id = $user->id;
            }
            if ($parent) {
                $policy->renewal_parent_id = $parent->id;
            }
            $policy->save();

            return $policy->load('provider', 'car', 'monthlyCar', 'parent', 'renewal');
        }, 3);
    }

    public function transition(InsurancePolicy $policy, string $action): InsurancePolicy
    {
        return DB::transaction(function () use ($policy, $action): InsurancePolicy {
            $policy = InsurancePolicy::query()->lockForUpdate()->findOrFail($policy->id);
            $column = $action === 'archive' ? 'archived_at' : 'cancelled_at';
            if (! $policy->{$column}) {
                $policy->{$column} = now();
                $policy->save();
            }

            return $policy->load('provider', 'car', 'monthlyCar', 'parent', 'renewal');
        }, 3);
    }
}
