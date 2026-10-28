<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaimEvent extends Model
{
    protected $table = 'insurance_claim_events';

    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaim::class, 'claim_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'changed_by');
    }
}
