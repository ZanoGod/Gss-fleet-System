<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InsuranceDocument extends Model
{
    protected $table = 'insurance_documents';

    protected $fillable = [
        'policy_id', 'storage_key', 'original_name', 'mime_type', 'size', 'archived_at', 'replaces_id',
    ];

    protected $hidden = ['storage_key'];

    protected function casts(): array
    {
        return ['size' => 'integer', 'archived_at' => 'datetime'];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_id');
    }

    public function replacement(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_id');
    }
}
