<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyBookingRequest;
use App\Http\Resources\DailyBookingResource;
use App\Models\Car;
use App\Models\DailyBooking;
use App\Models\Driver;
use App\Models\Operator;
use App\Services\DailyBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DailyBookingController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', DailyBooking::class);
        $query = DailyBooking::query()->with('car', 'secondaryCar', 'driver', 'operator')
            ->when($request->filled('status') && in_array($request->input('status'), DailyBooking::STATUSES, true), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('start_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('end_date', '<=', $request->input('date_to')))
            ->when($request->filled('car_id'), fn ($q) => $q->where(fn ($s) => $s->where('car_id', $request->integer('car_id'))->orWhere('secondary_car_id', $request->integer('car_id'))))
            ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_id', $request->integer('driver_id')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(function ($s) use ($term): void {
                    $s->where('guest_company_name', 'like', $term)->orWhere('operator_name', 'like', $term)
                        ->orWhere('custom_car_name', 'like', $term)->orWhere('custom_driver_name', 'like', $term)
                        ->orWhere('even_odd', 'like', $term)->orWhere('remark', 'like', $term)
                        ->orWhereHas('car', fn ($c) => $c->where('plate_no', 'like', $term)->orWhere('model_name', 'like', $term))
                        ->orWhereHas('secondaryCar', fn ($c) => $c->where('plate_no', 'like', $term)->orWhere('model_name', 'like', $term))
                        ->orWhereHas('driver', fn ($d) => $d->where('full_name', 'like', $term));
                });
            });
        $sort = $request->input('sort', 'updated_at');
        $direction = $request->input('direction', 'desc');
        if (! in_array($sort, ['updated_at', 'start_date', 'end_date', 'status', 'guest_company_name'], true)) $sort = 'updated_at';
        if (! in_array($direction, ['asc', 'desc'], true)) $direction = 'desc';
        return DailyBookingResource::collection($query->orderBy($sort, $direction)->paginate(min(max((int) $request->input('per_page', 15), 1), 100))->withQueryString());
    }

    public function store(DailyBookingRequest $request, DailyBookingService $service)
    {
        Gate::authorize('create', DailyBooking::class);
        return (new DailyBookingResource($service->save($request->validated())))->response()->setStatusCode(201);
    }

    public function show(DailyBooking $dailyBooking)
    {
        Gate::authorize('view', $dailyBooking);
        return new DailyBookingResource($dailyBooking->load('car', 'secondaryCar', 'driver', 'operator'));
    }

    public function update(DailyBookingRequest $request, DailyBooking $dailyBooking, DailyBookingService $service)
    {
        Gate::authorize('update', $dailyBooking);
        return new DailyBookingResource($service->save($request->validated(), $dailyBooking));
    }

    public function destroy(DailyBooking $dailyBooking, DailyBookingService $service)
    {
        Gate::authorize('delete', $dailyBooking);
        $service->delete($dailyBooking);
        return response()->noContent();
    }

    public function options()
    {
        Gate::authorize('viewAny', DailyBooking::class);
        return response()->json(['data' => [
            'cars' => Car::query()->orderBy('plate_no')->get(['id', 'plate_no', 'model_name', 'availability_status']),
            'drivers' => Driver::query()->orderBy('full_name')->get(['id', 'full_name', 'driver_status']),
            'operators' => Operator::query()->where('operator_status', 'Active')->orderBy('full_name')->get(['id', 'full_name']),
            'statuses' => DailyBooking::STATUSES,
        ]]);
    }
}
