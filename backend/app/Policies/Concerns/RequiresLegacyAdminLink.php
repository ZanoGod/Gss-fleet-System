<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Services\LegacyAdminIdentity;
use Illuminate\Auth\Access\AuthorizationException;

trait RequiresLegacyAdminLink
{
    protected function hasActiveLegacyAdmin(User $user): bool
    {
        try {
            app(LegacyAdminIdentity::class)->requireActiveAdminId($user);
            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }
}
