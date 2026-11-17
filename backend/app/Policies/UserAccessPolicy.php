<?php

namespace App\Policies;

use App\Models\User;

class UserAccessPolicy
{
    public function viewUsers(User $user): bool { return $user->hasPermission('users.view'); }
    public function createUser(User $user): bool { return $user->hasPermission('users.create'); }
    public function updateUser(User $user): bool { return $user->hasPermission('users.update'); }
    public function deleteUser(User $user): bool { return $user->hasPermission('users.delete'); }
    public function manageAccess(User $user): bool { return $user->hasPermission('access.manage'); }
    public function viewRoles(User $user): bool { return $user->hasPermission('roles.view'); }
    public function manageRoles(User $user): bool { return $user->hasPermission('roles.manage'); }
}
