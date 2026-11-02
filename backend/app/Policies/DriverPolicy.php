<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Same authenticated-admin access convention as existing daily bookings. */
class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Model $driver): bool
    {
        return $user->exists;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, Model $driver): bool
    {
        return $user->exists;
    }

    public function delete(User $user, Model $driver): bool
    {
        return $user->exists;
    }
}
