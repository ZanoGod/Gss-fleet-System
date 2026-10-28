<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\InsurancePolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InsuranceClaimService
{
    

    public function save(array $data, User $user, ?InsuranceClaim $existing = null): InsuranceClaim
    {
        return DB::transaction(function () use ($data, $user, $existing): InsuranceClaim {
            
            $policy = InsurancePolicy::query()->lockForUpdate()->findOrFail($data['policy_id']);
            $claim = $existing
                ? InsuranceClaim::query()->lockForUpdate()->findOrFail($existing->id)
                : new InsuranceClaim();

            if ($claim->exists && (int) $claim->policy_id !== (int) $policy->id) {
                throw ValidationException::withMessages(['policy_id' => 'Claims cannot be moved between policies.']);
            }

            $incident = $data['incident_date'];
            if ($incident < $policy->start_date->format('Y-m-d')
                || $incident > $policy->expiration_date->format('Y-m-d')
                || $incident > today()->toDateString()
                || ($policy->cancelled_at && $incident > $policy->cancelled_at->toDateString())) {
                throw ValidationException::withMessages(['incident_date' => 'Incident must be on or before today and within the policy coverage dates.']);
            }

            $claim->fill($data)->save();
            $claim->events()->create([
                'status' => $claim->status,
                'amount' => $claim->amount,
                'notes' => $claim->notes,
                'changed_by_user_id' => $user->id,
            ]);

            return $claim->load('policy.provider', 'events');
        }, 3);
    }
}
