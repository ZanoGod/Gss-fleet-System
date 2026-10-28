<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InsuranceClaimResource;
use App\Http\Resources\InsurancePolicyResource;
use App\Models\Car;
use App\Models\InsuranceClaim;
use App\Models\InsurancePolicy;
use App\Models\MonthlyCar;
use Illuminate\Support\Facades\Gate;

class InsuranceDashboardController extends Controller
{
    public function __invoke()
    {
        Gate::authorize('viewAny', InsurancePolicy::class);
        $base = InsurancePolicy::query();
        $active = (clone $base)->status('active');
        $soon = (clone $base)->status('expiring');
        $dailyInsured = InsurancePolicy::query()->live()->whereNotNull('car_id')->distinct()->pluck('car_id');
        $monthlyInsured = InsurancePolicy::query()->live()->whereNotNull('monthly_car_id')->distinct()->pluck('monthly_car_id');
        $expiringPolicies = (clone $soon)->whereDoesntHave('renewal')->with('provider', 'car', 'monthlyCar')->orderBy('expiration_date')->limit(10)->get();

        return response()->json(['data' => [
            'total_policies' => (clone $base)->count(),
            'active_policies' => (clone $active)->count(),
            'expired_policies' => (clone $base)->status('expired')->count(),
            'expiring_soon_policies' => (clone $soon)->count(),
            'scheduled_policies' => (clone $base)->status('scheduled')->count(),
            'claims' => [
                'total' => InsuranceClaim::query()->count(),
                'open' => InsuranceClaim::query()->whereIn('status', ['submitted', 'under_review'])->count(),
                'approved' => InsuranceClaim::query()->where('status', 'approved')->count(),
                'paid' => InsuranceClaim::query()->where('status', 'paid')->count(),
            ],
            'uninsured_vehicles' => [
                'daily' => Car::query()->whereNotIn('id', $dailyInsured)->count(),
                'monthly' => MonthlyCar::query()->where('availability_status', '!=', 'Inactive')->whereNotIn('id', $monthlyInsured)->count(),
            ],
            'expiring_policies' => InsurancePolicyResource::collection($expiringPolicies),
            'recent_claims' => InsuranceClaimResource::collection(InsuranceClaim::query()->with('policy.provider')->latest()->limit(5)->get()),
        ]]);
    }
}
