<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    public const STATUSES = ['Active', 'Inactive'];

    protected $table = 'customers';

    protected $fillable = [
        'company_name', 'guest_name', 'contact_person', 'contact_phone', 'contact_email',
        'customer_address', 'off_day', 'service_hour', 'customer_status', 'remark',
    ];

    public function monthlyContracts(): HasMany
    {
        return $this->hasMany(MonthlyContract::class, 'customer_id');
    }
}
