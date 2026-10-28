<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsuranceClaimRequest;
use App\Http\Resources\InsuranceClaimResource;
use App\Models\InsuranceClaim;
use App\Services\InsuranceClaimService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InsuranceClaimController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InsuranceClaim::class);
        $claims = InsuranceClaim::query()->with('policy.provider')->with('events.actor')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('policy_id'), fn ($q) => $q->where('policy_id', $request->integer('policy_id')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(function ($s) use ($term): void {
                    $s->where('reference_number', 'like', $term)
                        ->orWhereHas('policy', fn ($p) => $p->where('policy_number', 'like', $term));
                });
            })
            ->orderByDesc('incident_date')->orderByDesc('id')->paginate(min(max((int) $request->input('per_page', 15), 1), 100));
        return InsuranceClaimResource::collection($claims);
    }

    public function store(InsuranceClaimRequest $request, InsuranceClaimService $service)
    {
        Gate::authorize('create', InsuranceClaim::class);
        return (new InsuranceClaimResource($service->save($request->validated(), $request->user())))->response()->setStatusCode(201);
    }

    public function show(InsuranceClaim $claim)
    {
        Gate::authorize('view', $claim);
        return new InsuranceClaimResource($claim->load('policy.provider', 'events.actor'));
    }

    public function update(InsuranceClaimRequest $request, InsuranceClaim $claim, InsuranceClaimService $service)
    {
        Gate::authorize('update', $claim);
        return new InsuranceClaimResource($service->save($request->validated(), $request->user(), $claim));
    }
}
