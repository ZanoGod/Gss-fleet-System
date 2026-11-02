<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OperatorResource;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OperatorController extends Controller
{
    private function query(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:500', 'status' => ['nullable', Rule::in(Operator::STATUSES)]]);
        return Operator::query()->when($request->filled('status'), fn ($q) => $q->where('operator_status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('full_name', 'like', '%'.$request->input('search').'%')->orWhere('phone_number', 'like', '%'.$request->input('search').'%')->orWhere('note', 'like', '%'.$request->input('search').'%')));
    }
    public function index(Request $request) { return OperatorResource::collection($this->query($request)->orderByDesc('updated_at')->orderByDesc('id')->paginate(min(100, max(1, $request->integer('per_page', 10))))); }
    public function summary(Request $request)
    {
        $q = $this->query($request);
        return response()->json(['data' => ['total' => (clone $q)->count(), 'active' => (clone $q)->where('operator_status', 'Active')->count(), 'inactive' => (clone $q)->where('operator_status', 'Inactive')->count()]]);
    }
    private function data(Request $request, ?Operator $operator = null): array
    {
        return $request->validate(['full_name' => ['required', 'string', 'max:150', Rule::unique('operators', 'full_name')->ignore($operator?->id)], 'phone_number' => 'nullable|string|max:30', 'operator_status' => ['required', Rule::in(Operator::STATUSES)], 'note' => 'nullable|string|max:20000']);
    }
    public function store(Request $request) { return (new OperatorResource(Operator::create($this->data($request))))->response()->setStatusCode(201); }
    public function show(Operator $operator) { return new OperatorResource($operator->load('bookings', 'monthlyContracts')); }
    public function update(Request $request, Operator $operator)
    {
        return DB::transaction(function () use ($request, $operator) {
            $operator = Operator::query()->lockForUpdate()->findOrFail($operator->id);
            $operator->fill($this->data($request, $operator));
            if ($operator->isDirty('full_name')) {
                // Verified operator-edit.php behavior: synchronize every ID-linked DAILY booking name.
                // Monthly contracts retain their independently captured snapshots.
                $operator->bookings()->update(['operator_name' => $operator->full_name]);
            }
            $operator->save();
            return new OperatorResource($operator);
        }, 3);
    }
    public function destroy(Operator $operator)
    {
        DB::transaction(function () use ($operator) {
            $operator = Operator::query()->lockForUpdate()->findOrFail($operator->id);
            if ($operator->bookings()->exists() || $operator->monthlyContracts()->exists()) throw ValidationException::withMessages(['operator' => 'This operator has booking or monthly contract history and cannot be deleted.']);
            $operator->delete();
        }, 3);
        return response()->noContent();
    }
}
