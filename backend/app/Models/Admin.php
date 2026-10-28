<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Legacy admin records remain separate from Sanctum-authenticated Laravel users. */
class Admin extends Model
{
    protected $table = 'admins';

    protected $fillable = ['username', 'email', 'password_hash', 'is_active', 'last_login_at'];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function createdInsurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'created_by');
    }

    public function insuranceClaimEvents(): HasMany
    {
        return $this->hasMany(InsuranceClaimEvent::class, 'changed_by');
    }
}
