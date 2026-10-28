<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsuranceProviderRequest;
use App\Http\Resources\InsuranceProviderResource;
use App\Models\InsuranceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class InsuranceProviderController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InsuranceProvider::class);
        $providers = InsuranceProvider::query()->withCount('policies')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->orderBy('name')->paginate(min(max((int) $request->input('per_page', 15), 1), 100));

        return InsuranceProviderResource::collection($providers);
    }

    public function store(InsuranceProviderRequest $request)
    {
        Gate::authorize('create', InsuranceProvider::class);
        return (new InsuranceProviderResource(InsuranceProvider::create($request->validated())))->response()->setStatusCode(201);
    }

    public function show(InsuranceProvider $provider)
    {
        Gate::authorize('view', $provider);
        return new InsuranceProviderResource($provider->loadCount('policies'));
    }

    public function update(InsuranceProviderRequest $request, InsuranceProvider $provider)
    {
        Gate::authorize('update', $provider);
        $provider->update($request->validated());
        return new InsuranceProviderResource($provider->refresh()->loadCount('policies'));
    }

    public function destroy(InsuranceProvider $provider)
    {
        Gate::authorize('delete', $provider);
        if ($provider->policies()->exists()) {
            throw ValidationException::withMessages(['provider' => 'A provider with policies cannot be deleted.']);
        }
        $provider->delete();
        return response()->noContent();
    }
}
