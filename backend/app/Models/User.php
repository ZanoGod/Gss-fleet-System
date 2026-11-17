<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'role_id', 'is_active', 'approved_at'];

    protected $attributes = ['is_active' => true];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean', 'approved_at' => 'datetime'];
    }

    public function role(): BelongsTo { return $this->belongsTo(Role::class); }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active || $this->role_id === null) return false;
        $role = $this->role;
        if ($role?->slug === 'administrator' || ($role?->is_system && $role->slug === 'admin')) return true;

        $permissions = $this->role?->permissions ?? [];
        if (in_array($permission, $permissions, true)) return true;
        if (str_ends_with($permission, '.view') && in_array(substr($permission, 0, -5).'.manage', $permissions, true)) return true;
        if ($permission === 'users.view') {
            return count(array_intersect($permissions, ['users.create', 'users.update', 'users.delete', 'access.manage', 'roles.view', 'roles.manage'])) > 0;
        }
        return $permission === 'roles.view'
            && (in_array('roles.manage', $permissions, true) || in_array('users.view', $permissions, true));
    }

    /** Explicit, manually provisioned link; legacy admin credentials are never copied here. */
    public function legacyAdminLink(): HasOne
    {
        return $this->hasOne(AdminUserLink::class, 'user_id');
    }
}
