<?php

namespace App\Policies;

use App\Models\InsuranceClaim;
use App\Models\User;
use App\Policies\Concerns\RequiresLegacyAdminLink;

class InsuranceClaimPolicy
{
    use RequiresLegacyAdminLink;

    public function viewAny(User $user): bool { return $user->exists; }
    public function view(User $user, InsuranceClaim $claim): bool { return $user->exists; }
    public function create(User $user): bool { return $user->exists; }
    public function update(User $user, InsuranceClaim $claim): bool { return $user->exists; }
}
