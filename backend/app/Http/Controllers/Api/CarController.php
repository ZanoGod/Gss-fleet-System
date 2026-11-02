<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FleetVehicleResource;
use App\Models\Car;
use App\Models\MonthlyCar;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** One API, explicit fleet scope. IDs never cross the daily/monthly boundary. */
class CarController extends Controller
{
    private function model(Request $request): string
    {
        $request->validate(['resource_type' => ['nullable', Rule::in(['daily', 'monthly'])]]);
        return $request->input('resource_type', 'daily') === 'monthly' ? MonthlyCar::class : Car::class;
    }

    private function query(Request $request)
    {
        $model = $this->model($request);
        $monthly = $model === MonthlyCar::class;
        $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'status' => ['nullable', Rule::in($model::STATUSES)], 'search' => 'nullable|string|max:500']);
        $month = Carbon::createFromFormat('!Y-m', $request->input('month', now()->format('Y-m')));
        $assignment = fn ($q) => $q->with('driver')->where('status', '!=', 'Cancelled')->whereDate('start_date', '<=', $month->copy()->endOfMonth())->whereDate('end_date', '>=', $month);
        $relations = $monthly ? ['contracts' => $assignment] : ['bookings' => $assignment, 'secondaryBookings' => $assignment];
        return $model::query()->with($relations)
            ->when($request->filled('status'), fn ($q) => $q->where('availability_status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request, $monthly) {
                $term = '%'.$request->input('search').'%';
                $q->where(function ($q) use ($term, $monthly) {
                    $q->where('plate_no', 'like', $term)->orWhere('car_type', 'like', $term)->orWhere('model_name', 'like', $term)
                        ->orWhereHas($monthly ? 'contracts' : 'bookings', fn ($q) => $q->where($monthly ? 'customer_name' : 'guest_company_name', 'like', $term));
                    if (! $monthly) $q->orWhereHas('secondaryBookings', fn ($q) => $q->where('guest_company_name', 'like', $term));
                });
            });
    }

    public function index(Request $request)
    {
        return FleetVehicleResource::collection($this->query($request)->orderByDesc('id')->paginate(min(100, max(1, $request->integer('per_page', 10)))));
    }

    public function summary(Request $request)
    {
        $query = $this->query($request);
        $model = $this->model($request);
        $counts = ['total' => (clone $query)->count()];
        foreach ($model::STATUSES as $status) $counts[strtolower($status)] = (clone $query)->where('availability_status', $status)->count();
        return response()->json(['data' => $counts]);
    }

    private function data(Request $request, $record = null): array
    {
        $model = $this->model($request);
        return $request->validate([
            'car_type' => 'required|string|max:100', 'plate_no' => ['required', 'string', 'max:50', Rule::unique((new $model)->getTable(), 'plate_no')->ignore($record?->id)],
            'model_name' => 'required|string|max:100', 'seat_capacity' => 'required|integer|min:1|max:255',
            'availability_status' => ['required', Rule::in($model::STATUSES)], 'note' => 'nullable|string|max:20000',
        ]);
    }

    public function store(Request $request)
    {
        $model = $this->model($request);
        return (new FleetVehicleResource($model::create($this->data($request))))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $car)
    {
        $model = $this->model($request);
        $record = $model::findOrFail($car);
        $record->load($record instanceof MonthlyCar ? ['contracts.driver', 'insurancePolicies', 'maintenances'] : ['bookings.driver', 'secondaryBookings.driver', 'insurancePolicies', 'maintenances']);
        return new FleetVehicleResource($record);
    }

    public function update(Request $request, string $car)
    {
        $model = $this->model($request);
        return DB::transaction(function () use ($request, $model, $car) {
            $record = $model::query()->lockForUpdate()->findOrFail($car);
            $record->update($this->data($request, $record));
            return new FleetVehicleResource($record);
        }, 3);
    }

    public function destroy(Request $request, string $car)
    {
        $model = $this->model($request);
        DB::transaction(function () use ($model, $car) {
            $record = $model::query()->lockForUpdate()->findOrFail($car);
            $relations = $record instanceof MonthlyCar ? ['contracts', 'insurancePolicies', 'maintenances'] : ['bookings', 'secondaryBookings', 'insurancePolicies', 'maintenances'];
            foreach ($relations as $relation) {
                if ($record->{$relation}()->exists()) throw ValidationException::withMessages(['vehicle' => 'This vehicle has assignment, insurance, or maintenance history and cannot be deleted.']);
            }
            $record->delete();
        }, 3);
        return response()->noContent();
    }
}
