<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarLicenseDocument extends Model
{
    protected $fillable = ['car_license_id', 'storage_key', 'original_name', 'mime_type', 'size'];

    public function license(): BelongsTo { return $this->belongsTo(CarLicense::class, 'car_license_id'); }
}
