<?php

namespace App\Policies;

use App\Models\InsuranceProvider;
use App\Models\User;
use App\Policies\Concerns\RequiresLegacyAdminLink;

class InsuranceProviderPolicy
{
    use RequiresLegacyAdminLink;

    public function viewAny(User $user): bool { return $user->exists; }
    public function view(User $user, InsuranceProvider $provider): bool { return $user->exists; }
    public function create(User $user): bool { return $user->exists; }
    public function update(User $user, InsuranceProvider $provider): bool { return $user->exists; }
    public function delete(User $user, InsuranceProvider $provider): bool { return $user->exists; }
}
