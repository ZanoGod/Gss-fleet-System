<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InsurancePolicy extends Model
{
    public const CURRENCIES = ['MMK', 'USD'];
    public const STATUSES = ['active', 'expiring', 'expired', 'scheduled', 'cancelled', 'archived'];

    protected $table = 'insurance_policies';

    protected $fillable = [
        'car_id', 'monthly_car_id', 'provider_id', 'policy_number', 'insurance_type',
        'coverage_details', 'start_date', 'expiration_date', 'premium_amount', 'currency',
        'coverage_amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'expiration_date' => 'date:Y-m-d',
            'premium_amount' => 'decimal:2',
            'coverage_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function monthlyCar(): BelongsTo
    {
        return $this->belongsTo(MonthlyCar::class, 'monthly_car_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewal_parent_id');
    }

    public function renewal(): HasOne
    {
        return $this->hasOne(self::class, 'renewal_parent_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'policy_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InsuranceDocument::class, 'policy_id');
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->whereNull('cancelled_at');
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        $today = today()->toDateString();

        return match ($status) {
            'archived' => $query->whereNotNull('archived_at'),
            'cancelled' => $query->whereNull('archived_at')->whereNotNull('cancelled_at'),
            'expired' => $query->live()->where('expiration_date', '<', $today),
            'scheduled' => $query->live()->where('start_date', '>', $today),
            'active' => $query->live()->where('start_date', '<=', $today)->where('expiration_date', '>=', $today),
            'expiring' => $query->status('active')->where('expiration_date', '<=', today()->addDays(config('insurance.expiring_days', 30))->toDateString()),
            default => $query,
        };
    }

    public function getStatusAttribute(): string
    {
        if ($this->archived_at) {
            return 'archived';
        }
        if ($this->cancelled_at) {
            return 'cancelled';
        }

        $today = today()->toDateString();
        if ($this->expiration_date < $today) {
            return 'expired';
        }
        if ($this->start_date > $today) {
            return 'scheduled';
        }

        return $this->expiration_date <= today()->addDays(config('insurance.expiring_days', 30))->toDateString()
            ? 'expiring'
            : 'active';
    }

    public function getVehicleAttribute(): ?array
    {
        $vehicle = $this->car_id ? $this->car : $this->monthlyCar;
        if (! $vehicle) {
            return null;
        }

        return [
            'id' => $vehicle->id,
            'fleet' => $this->car_id ? 'daily' : 'monthly',
            'plate_no' => $vehicle->plate_no,
            'model_name' => $vehicle->model_name,
        ];
    }
}
