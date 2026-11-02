<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaintenanceRequest;
use App\Http\Resources\MaintenanceResource;
use App\Models\{Car, MonthlyCar, VehicleMaintenance};
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    private function query(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:500', 'status' => ['nullable', Rule::in(VehicleMaintenance::STATUSES)], 'maintenance_type' => ['nullable', Rule::in(VehicleMaintenance::TYPES)], 'date_from' => 'nullable|date_format:Y-m-d', 'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from', 'vehicle' => ['nullable', 'regex:/^(daily|monthly):[1-9][0-9]*$/']]);
        return VehicleMaintenance::query()->with('car', 'monthlyCar')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('maintenance_type'), fn ($q) => $q->where('maintenance_type', $request->input('maintenance_type')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('scheduled_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('scheduled_date', '<=', $request->input('date_to')))
            ->when($request->filled('vehicle'), function ($q) use ($request) { [$type, $id] = explode(':', $request->input('vehicle')); $q->where($type === 'daily' ? 'car_id' : 'monthly_car_id', $id); })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->input('search').'%';
                $q->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term)->orWhere('service_provider', 'like', $term)->orWhere('notes', 'like', $term)->orWhereHas('car', fn ($q) => $q->where('plate_no', 'like', $term)->orWhere('model_name', 'like', $term))->orWhereHas('monthlyCar', fn ($q) => $q->where('plate_no', 'like', $term)->orWhere('model_name', 'like', $term)));
            });
    }
    public function index(Request $request) { return MaintenanceResource::collection($this->query($request)->orderByDesc('scheduled_date')->orderByDesc('id')->paginate(min(100, max(1, $request->integer('per_page', 10))))); }
    public function summary(Request $request)
    {
        $q = $this->query($request);
        $data = ['total' => (clone $q)->count()];
        foreach (VehicleMaintenance::STATUSES as $status) $data[strtolower(str_replace(' ', '_', $status))] = (clone $q)->where('status', $status)->count();
        $data['actual_costs'] = (clone $q)->reorder()->selectRaw('currency, SUM(actual_cost) AS total')->whereNotNull('actual_cost')->where('status', '!=', 'Cancelled')->groupBy('currency')->get()->map(fn ($r) => ['currency' => $r->currency, 'total' => number_format((float) $r->total, 2, '.', '')]);
        $today = now()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $previousMonthEnd = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
        $dashboard = VehicleMaintenance::query();
        $data['dashboard'] = [
            'due_this_week' => (clone $dashboard)->where('status', 'Scheduled')->whereDate('scheduled_date', '<=', $weekEnd)->count(),
            'overdue' => (clone $dashboard)->where('status', 'Scheduled')->whereDate('scheduled_date', '<', $today)->count(),
            'in_workshop' => (clone $dashboard)->where('status', 'In Progress')->count(),
            'completed_this_month' => (clone $dashboard)->where('status', 'Completed')->whereBetween('completed_at', [$monthStart, $monthEnd])->count(),
            'completed_previous_month' => (clone $dashboard)->where('status', 'Completed')->whereBetween('completed_at', [$previousMonthStart, $previousMonthEnd])->count(),
            'spend_this_month' => (clone $dashboard)->reorder()->selectRaw('currency, SUM(actual_cost) AS total')
                ->where('status', 'Completed')->whereBetween('completed_at', [$monthStart, $monthEnd])
                ->whereNotNull('actual_cost')->groupBy('currency')->get()
                ->map(fn ($r) => ['currency' => $r->currency, 'total' => number_format((float) $r->total, 2, '.', '')]),
            'month_label' => now()->format('F'),
            'previous_month_label' => now()->subMonthNoOverflow()->format('F'),
        ];
        return response()->json(['data' => $data]);
    }
    public function options()
    {
        $vehicles = collect();
        foreach (['daily' => Car::class, 'monthly' => MonthlyCar::class] as $type => $model) $vehicles = $vehicles->merge($model::query()->orderBy('plate_no')->get(['id', 'plate_no', 'model_name', 'availability_status'])->map(fn ($v) => array_merge($v->toArray(), ['resource_type' => $type])));
        return response()->json(['data' => ['vehicles' => $vehicles, 'types' => VehicleMaintenance::TYPES, 'statuses' => VehicleMaintenance::STATUSES]]);
    }
    public function store(MaintenanceRequest $request, MaintenanceService $service) { return (new MaintenanceResource($service->save($request->validated(), $request->user())))->response()->setStatusCode(201); }
    public function show(VehicleMaintenance $maintenance) { return new MaintenanceResource($maintenance->load('car', 'monthlyCar')); }
    public function update(MaintenanceRequest $request, VehicleMaintenance $maintenance, MaintenanceService $service) { return new MaintenanceResource($service->save($request->validated(), $request->user(), $maintenance)); }
    public function destroy(VehicleMaintenance $maintenance, MaintenanceService $service) { $service->delete($maintenance); return response()->noContent(); }
}
