<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerIndexRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function index(CustomerIndexRequest $request)
    {
        Gate::authorize('viewAny', Customer::class);
        $filters = $request->validated();
        $query = $this->aggregates(Customer::query());
        $term = trim($filters['search'] ?? '');
        if ($term !== '') {
            $query->where(function (Builder $search) use ($term): void {
                foreach (['company_name', 'guest_name', 'contact_person', 'contact_phone', 'contact_email', 'customer_address', 'remark'] as $field) {
                    $search->orWhere($field, 'like', '%'.$term.'%');
                }
                $search->orWhereHas('monthlyContracts', function (Builder $contracts) use ($term): void {
                    $contracts->where(function (Builder $text) use ($term): void {
                        $text->where('contract_no', 'like', '%'.$term.'%')->orWhere('operator_name', 'like', '%'.$term.'%');
                    });
                });
            });
        }
        if (! empty($filters['status'])) {
            $query->where('customer_status', $filters['status']);
        }
        // Deliberately separate EXISTS queries: legacy filters may match different contracts.
        foreach (['assignment_type', 'rental_status'] as $field) {
            if (! empty($filters[$field])) {
                $query->whereHas('monthlyContracts', fn (Builder $contracts) => $contracts->where($field, $filters[$field]));
            }
        }
        if (! empty($filters['expiry'])) {
            $today = Carbon::today()->toDateString();
            $query->whereHas('monthlyContracts', function (Builder $contracts) use ($filters, $today): void {
                if ($filters['expiry'] === 'expired') {
                    $contracts->where('end_date', '<', $today);
                } else {
                    $contracts->where('end_date', '>=', $today)
                        ->where('end_date', '<', Carbon::today()->addDays(60)->toDateString());
                }
            });
        }
        return CustomerResource::collection($query->orderBy('company_name')->orderBy('id')->paginate(10)->withQueryString());
    }

    public function summary()
    {
        Gate::authorize('viewAny', Customer::class);
        return response()->json(['data' => [
            'total' => Customer::query()->count(),
            'active' => Customer::query()->where('customer_status', 'Active')->count(),
            'with_contracts' => Customer::query()->has('monthlyContracts')->count(),
        ]]);
    }

    public function show(CustomerIndexRequest $request, Customer $customer)
    {
        Gate::authorize('view', $customer);
        $month = Carbon::createFromFormat('!Y-m', $request->validated('month') ?: now()->format('Y-m'));
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();
        $record = $this->aggregates(Customer::query())->findOrFail($customer->id);
        $record->load(['monthlyContracts' => function (Builder $contracts) use ($start, $end): void {
            $contracts->with('car', 'driver', 'operator')
                ->where('start_date', '<=', $end)->where('end_date', '>=', $start)
                ->whereNotIn('status', ['Cancelled', 'Canceled', 'Cancel'])
                ->orderBy('end_date')->orderByDesc('id');
        }]);
        return (new CustomerResource($record))->additional(['meta' => ['month' => $month->format('Y-m')]]);
    }

    public function store(StoreCustomerRequest $request, CustomerService $service)
    {
        $customer = $service->save($request->validated());
        return (new CustomerResource($this->aggregates(Customer::query())->findOrFail($customer->id)))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerService $service)
    {
        $record = $service->save($request->validated(), $customer);
        return new CustomerResource($this->aggregates(Customer::query())->findOrFail($record->id));
    }

    public function destroy(Customer $customer, CustomerService $service)
    {
        Gate::authorize('delete', $customer);
        if (! $service->delete($customer)) {
            return response()->json([
                'success' => false,
                'code' => 'CUSTOMER_HAS_CONTRACTS',
                'message' => 'This customer is assigned to one or more monthly contracts and cannot be deleted.',
                'errors' => (object) [],
            ], 409);
        }
        return response()->noContent();
    }

    private function aggregates(Builder $query): Builder
    {
        return $query->withCount([
            'monthlyContracts',
            'monthlyContracts as active_contract_count' => fn (Builder $contracts) => $contracts->where('status', 'Active'),
        ])->withMax('monthlyContracts as latest_expiry', 'end_date');
    }
}
