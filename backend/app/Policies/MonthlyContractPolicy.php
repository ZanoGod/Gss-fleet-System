<?php

namespace App\Policies;

use App\Models\MonthlyContract;
use App\Models\User;

/** The current application defines authentication but no approved role catalog. */
class MonthlyContractPolicy
{
    public function viewAny(User $user): bool { return $user->exists; }
    public function view(User $user, MonthlyContract $contract): bool { return $user->exists; }
    public function create(User $user): bool { return $user->exists; }
    public function update(User $user, MonthlyContract $contract): bool { return $user->exists; }
    public function delete(User $user, MonthlyContract $contract): bool { return $user->exists; }
}
