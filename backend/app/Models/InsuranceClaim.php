<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceClaim extends Model
{
    public const STATUSES = ['submitted', 'under_review', 'approved', 'rejected', 'paid', 'closed'];

    protected $table = 'insurance_claims';

    protected $fillable = ['policy_id', 'incident_date', 'reference_number', 'amount', 'status', 'notes'];

    protected function casts(): array
    {
        return ['incident_date' => 'date:Y-m-d', 'amount' => 'decimal:2'];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InsuranceClaimEvent::class, 'claim_id')->orderBy('id');
    }
}
