<?php

namespace App\Services;

use App\Models\DailyBooking;
use App\Models\Operator;
use Illuminate\Support\Facades\DB;

class DailyBookingService
{
    public function __construct(private readonly BookingConflictService $conflicts) {}

    public function save(array $data, ?DailyBooking $booking = null): DailyBooking
    {
        return DB::transaction(function () use ($data, $booking): DailyBooking {
            $existing = $booking ? DailyBooking::query()->lockForUpdate()->findOrFail($booking->id) : null;
            $this->conflicts->lockAndValidate($data, $existing?->id);
            $operator = Operator::query()->where('operator_status', 'Active')->lockForUpdate()->findOrFail($data['operator_id']);
            $record = $existing ?? new DailyBooking();
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
