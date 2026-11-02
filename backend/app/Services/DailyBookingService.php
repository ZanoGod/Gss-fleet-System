<?php

namespace App\Services;

use App\Models\DailyBooking;
use App\Models\Operator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyBookingService
{
    public function __construct(private readonly BookingConflictService $conflicts) {}

    public function save(array $data, ?DailyBooking $booking = null): DailyBooking
    {
        return DB::transaction(function () use ($data, $booking): DailyBooking {
            $existing = $booking ? DailyBooking::query()->lockForUpdate()->findOrFail($booking->id) : null;
            $this->conflicts->lockAndValidate($data, $existing?->id);
            $operator = Operator::query()->lockForUpdate()->findOrFail($data['operator_id']);
            if ($operator->operator_status !== 'Active' && $existing?->operator_id !== $operator->id) {
                throw ValidationException::withMessages(['operator_id' => 'Choose an active operator for a new assignment.']);
            }
            $record = $existing ?? new DailyBooking;
            $record->fill($data);
            $record->operator_name = $operator->full_name;
            $record->save();

            return $record->load('car', 'secondaryCar', 'driver', 'operator');
        }, 3);
    }

    public function delete(DailyBooking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            DailyBooking::query()->lockForUpdate()->findOrFail($booking->id)->delete();
        }, 3);
    }
}
