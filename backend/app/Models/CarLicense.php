<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CarLicense extends Model
{
    public const PERIODS = [1, 2, 3];
    public const STATUSES = ['Active', 'Expiring Soon', 'Expired'];
    public const EXPIRING_DAYS = 30;

    protected $fillable = [
        'car_id', 'monthly_car_id', 'license_number', 'license_period', 'issue_date',
        'expiry_date', 'renewal_date', 'renewal_fee', 'office_region', 'office_branch', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'license_period' => 'integer', 'issue_date' => 'date:Y-m-d',
            'expiry_date' => 'date:Y-m-d', 'renewal_date' => 'date:Y-m-d', 'renewal_fee' => 'decimal:2',
        ];
    }

    public function car(): BelongsTo { return $this->belongsTo(Car::class); }
    public function monthlyCar(): BelongsTo { return $this->belongsTo(MonthlyCar::class, 'monthly_car_id'); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'renewal_parent_id'); }
    public function renewal(): HasOne { return $this->hasOne(self::class, 'renewal_parent_id'); }
    public function renewals(): HasMany { return $this->hasMany(self::class, 'renewal_parent_id')->orderByDesc('issue_date'); }
    public function documents(): HasMany { return $this->hasMany(CarLicenseDocument::class); }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        $today = today()->toDateString();
        $soon = today()->addDays(config('car_licenses.expiring_days', self::EXPIRING_DAYS))->toDateString();

        return match ($status) {
            'Expired' => $query->where('expiry_date', '<', $today),
            'Expiring Soon' => $query->whereBetween('expiry_date', [$today, $soon]),
            'Active' => $query->where('expiry_date', '>', $soon),
            default => $query,
        };
    }

    public function getStatusAttribute(): string
    {
        if ($this->expiry_date < today()->toDateString()) return 'Expired';
        if ($this->expiry_date <= today()->addDays(config('car_licenses.expiring_days', self::EXPIRING_DAYS))->toDateString()) return 'Expiring Soon';
        return 'Active';
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return (int) today()->diffInDays($this->expiry_date, false);
    }

    public function getVehicleAttribute(): ?array
    {
        $vehicle = $this->car_id ? $this->car : $this->monthlyCar;
        return $vehicle ? [
            'id' => $vehicle->id, 'fleet' => $this->car_id ? 'daily' : 'monthly',
            'plate_no' => $vehicle->plate_no, 'model_name' => $vehicle->model_name,
        ] : null;
    }
}
