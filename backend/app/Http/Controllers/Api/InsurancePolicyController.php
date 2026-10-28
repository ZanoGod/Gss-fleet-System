<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsurancePolicyRequest;
use App\Http\Resources\InsurancePolicyResource;
use App\Models\Car;
use App\Models\InsurancePolicy;
use App\Models\MonthlyCar;
use App\Services\InsurancePolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InsurancePolicyController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InsurancePolicy::class);
        $policies = InsurancePolicy::query()->with(['provider', 'car', 'monthlyCar', 'renewal'])
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($sub) => $sub->where('policy_number', 'like', $term)->orWhereHas('provider', fn ($p) => $p->where('name', 'like', $term)));
            })
            ->when($request->filled('status') && in_array($request->input('status'), InsurancePolicy::STATUSES, true), fn ($q) => $q->status($request->input('status')))
            ->when($request->filled('fleet') && $request->input('fleet') === 'daily', fn ($q) => $q->whereNotNull('car_id'))
            ->when($request->filled('fleet') && $request->input('fleet') === 'monthly', fn ($q) => $q->whereNotNull('monthly_car_id'))
            ->orderBy('expiration_date')->orderBy('id')->paginate(min(max((int) $request->input('per_page', 15), 1), 100));
        return InsurancePolicyResource::collection($policies);
    }

    public function store(InsurancePolicyRequest $request, InsurancePolicyService $service)
    {
        Gate::authorize('create', InsurancePolicy::class);
        return (new InsurancePolicyResource($service->save($request->validated(), $request->user())))->response()->setStatusCode(201);
    }

    public function show(InsurancePolicy $policy)
    {
        Gate::authorize('view', $policy);
        return new InsurancePolicyResource($policy->load(['provider', 'car', 'monthlyCar', 'parent', 'renewal', 'claims.events.actor', 'documents']));
    }

    public function update(InsurancePolicyRequest $request, InsurancePolicy $policy, InsurancePolicyService $service)
    {
        Gate::authorize('update', $policy);
        return new InsurancePolicyResource($service->save($request->validated(), $request->user(), $policy));
    }

    public function renew(InsurancePolicyRequest $request, InsurancePolicy $policy, InsurancePolicyService $service)
    {
        Gate::authorize('update', $policy);
        return (new InsurancePolicyResource($service->save($request->validated(), $request->user(), null, $policy)))->response()->setStatusCode(201);
    }

    public function cancel(InsurancePolicy $policy, InsurancePolicyService $service)
    {
        Gate::authorize('update', $policy);
        return new InsurancePolicyResource($service->transition($policy, 'cancel'));
    }

    public function archive(InsurancePolicy $policy, InsurancePolicyService $service)
    {
        Gate::authorize('update', $policy);
        return new InsurancePolicyResource($service->transition($policy, 'archive'));
    }

    public function vehicles(Request $request)
    {
        Gate::authorize('viewAny', InsurancePolicy::class);
        $fleet = $request->input('fleet');
        $daily = $fleet !== 'monthly' ? Car::query()->orderBy('plate_no')->get(['id', 'plate_no', 'model_name'])->map(fn ($v) => [...$v->toArray(), 'fleet' => 'daily']) : collect();
        $monthly = $fleet !== 'daily' ? MonthlyCar::query()->orderBy('plate_no')->get(['id', 'plate_no', 'model_name'])->map(fn ($v) => [...$v->toArray(), 'fleet' => 'monthly']) : collect();
        return response()->json(['data' => $daily->concat($monthly)->values()]);
    }
}
