<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceProvider extends Model
{
    protected $table = 'insurance_providers';

    protected $fillable = ['name', 'contact_name', 'email', 'phone', 'address', 'notes'];

    public function policies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'provider_id');
    }
}
