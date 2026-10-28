<?php

namespace App\Policies;

use App\Models\InsurancePolicy as PolicyRecord;
use App\Models\User;
use App\Policies\Concerns\RequiresLegacyAdminLink;

class InsurancePolicyPolicy
{
    use RequiresLegacyAdminLink;

    public function viewAny(User $user): bool { return $user->exists; }
    public function view(User $user, PolicyRecord $policy): bool { return $user->exists; }
    public function create(User $user): bool { return $user->exists; }
    public function update(User $user, PolicyRecord $policy): bool { return $user->exists; }
}
