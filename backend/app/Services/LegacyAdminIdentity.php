<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class LegacyAdminIdentity
{
    /** Resolve a Laravel session identity to its explicitly linked legacy admin key. */
    public function requireActiveAdminId(User $user): int
    {
        $link = $user->legacyAdminLink()->with('admin')->first();

        if ($link === null || $link->admin === null || ! $link->admin->is_active) {
            throw new AuthorizationException('An active, explicitly linked GSS admin account is required.');
        }

        return (int) $link->admin_id;
    }
}
