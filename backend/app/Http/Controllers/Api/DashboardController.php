<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailyBookingResource;
use App\Models\Car;
use App\Models\DailyBooking;
use App\Models\Driver;
use App\Models\MonthlyContract;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke()
    {
        Gate::authorize('viewAny', DailyBooking::class);
        Gate::authorize('viewAny', Driver::class);
        Gate::authorize('viewAny', MonthlyContract::class);

        $today = today();
        // Match assignment availability: pending bookings also reserve resources, dates are inclusive.
        $reservedToday = fn ($query) => $query->whereIn('status', ['Pending', 'Confirm'])
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today);
        $availableCars = Car::query()->where('availability_status', 'Available')
            ->whereDoesntHave('maintenances', fn ($query) => $query->where('status', 'In Progress'))
            ->whereDoesntHave('bookings', $reservedToday)->whereDoesntHave('secondaryBookings', $reservedToday);
        $availableDrivers = Driver::query()->where('driver_status', 'Available')
            ->whereDoesntHave('bookings', $reservedToday);

        return response()->json(['data' => [
            'total_bookings' => DailyBooking::query()->count(),
            'available_cars' => $availableCars->count(),
            'available_drivers' => $availableDrivers->count(),
            'ongoing_bookings' => DailyBooking::query()->where('status', 'Confirm')
                ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count(),
            'expiring_contracts' => MonthlyContract::query()->where('status', 'Active')
                ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', $today->copy()->addDays(30))->count(),
            'recent_bookings' => DailyBookingResource::collection(DailyBooking::query()
                ->with('car', 'secondaryCar', 'driver', 'operator')->orderByDesc('updated_at')->orderByDesc('id')->limit(5)->get()),
        ]]);
    }
}
