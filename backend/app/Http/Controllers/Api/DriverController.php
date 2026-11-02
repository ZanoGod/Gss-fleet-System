<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DriverIndexRequest;
use App\Http\Requests\DriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\MonthlyDriver;
use App\Services\DriverAvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
    public function __construct(private DriverAvailabilityService $availability) {}

    private function model($request): string
    {
        return $this->availability->model($request->input('resource_type') ?: 'daily');
    }

    private function query(DriverIndexRequest $request)
    {
        $model = $this->model($request);
        Gate::authorize('viewAny', $model);
        $monthly = $model === MonthlyDriver::class;
        $relation = $monthly ? 'contracts' : 'bookings';
        $month = Carbon::createFromFormat('!Y-m', $request->input('month') ?: now()->format('Y-m'));

        return $model::query()->with([$relation => fn ($q) => $q->with($monthly ? ['car'] : ['car', 'secondaryCar'])
            ->whereNotIn('status', ['Cancelled', 'Canceled', 'Cancel'])->whereDate('start_date', '<=', $month->copy()->endOfMonth())->whereDate('end_date', '>=', $month)->orderBy('start_date')->orderBy('end_date')->orderBy('id')])
            ->when($request->filled('status'), fn ($q) => $q->where('driver_status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request, $monthly, $relation) {
                $term = '%'.$request->input('search').'%';
                $q->where(fn ($q) => $q->where('full_name', 'like', $term)->orWhere('phone_number', 'like', $term)->orWhere('note', 'like', $term)
                    ->orWhereHas($relation, function ($q) use ($term, $monthly) {
                        $q->whereIn('status', $monthly ? ['Draft', 'Active'] : ['Pending', 'Confirm'])
                            ->where(function ($q) use ($term, $monthly) {
                                $q->where($monthly ? 'customer_name' : 'guest_company_name', 'like', $term);
                                if ($monthly) {
                                    $q->orWhere('contract_no', 'like', $term);
                                }
                            });
                    }));
            });
    }

    public function index(DriverIndexRequest $request)
    {
        return DriverResource::collection($this->query($request)->orderBy('full_name')->orderBy('id')->paginate(10)->withQueryString());
    }

    public function summary(DriverIndexRequest $request)
    {
        $model = $this->model($request);
        Gate::authorize('viewAny', $model);
        // Legacy summary cards describe the entire selected pool, independent of table filters/month.
        $q = $model::query();
        $data = ['total' => (clone $q)->count(), 'available' => (clone $q)->where('driver_status', 'Available')->count(), 'assigned' => (clone $q)->whereIn('driver_status', ['Assigned', 'On Trip'])->count(), 'leave' => (clone $q)->where('driver_status', 'Leave')->count(), 'inactive' => (clone $q)->where('driver_status', 'Inactive')->count()];

        return response()->json(['data' => $data]);
    }

    public function options(DriverIndexRequest $request)
    {
        $model = $this->model($request);
        Gate::authorize('viewAny', $model);
        $drivers = $model::query()->orderBy('full_name')->get(['id', 'full_name', 'phone_number', 'driver_status']);
        $drivers = $drivers->map(function ($driver) use ($request) {
            return array_merge($driver->toArray(), ['available' => $request->filled('date_from') ? $this->availability->available($driver, $request->input('date_from'), $request->input('date_to'), $request->integer('except_id') ?: null) : $driver->driver_status === 'Available']);
        });

        return response()->json(['data' => ['statuses' => $this->availability->statuses($request->input('resource_type') ?: 'daily'), 'drivers' => $drivers]]);
    }

    public function store(DriverRequest $request)
    {
        $model = $this->model($request);
        Gate::authorize('create', $model);

        return (new DriverResource($model::create($request->safe()->only(['full_name', 'phone_number', 'driver_status', 'note']))))->response()->setStatusCode(201);
    }

    public function show(DriverIndexRequest $request, string $driver)
    {
        $model = $this->model($request);
        $record = $model::findOrFail($driver);
        Gate::authorize('view', $record);

        return new DriverResource($record->load($record instanceof MonthlyDriver ? ['contracts.car'] : ['bookings.car', 'bookings.secondaryCar']));
    }

    public function update(DriverRequest $request, string $driver)
    {
        $model = $this->model($request);

        return DB::transaction(function () use ($request, $model, $driver) {
            $record = $model::query()->lockForUpdate()->findOrFail($driver);
            Gate::authorize('update', $record);
            $record->update($request->safe()->only(['full_name', 'phone_number', 'driver_status', 'note']));

            return new DriverResource($record);
        }, 3);
    }

    public function destroy(DriverIndexRequest $request, string $driver)
    {
        $model = $this->model($request);
        DB::transaction(function () use ($model, $driver) {
            $record = $model::query()->lockForUpdate()->findOrFail($driver);
            Gate::authorize('delete', $record);
            if ($this->availability->assignments($record)->exists()) {
                throw ValidationException::withMessages(['driver' => 'This driver has booking or monthly contract history and cannot be deleted. Use Inactive instead.']);
            }
            $record->delete();
        }, 3);

        return response()->noContent();
    }
}
