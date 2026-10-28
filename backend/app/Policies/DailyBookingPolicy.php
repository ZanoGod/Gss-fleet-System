<?php

namespace App\Policies;

use App\Models\DailyBooking;
use App\Models\User;

/** Legacy app had authenticated admins but no finer booking role permissions. */
class DailyBookingPolicy
{
    public function viewAny(User $user): bool { return $user->exists; }
    public function view(User $user, DailyBooking $booking): bool { return $user->exists; }
    public function create(User $user): bool { return $user->exists; }
    public function update(User $user, DailyBooking $booking): bool { return $user->exists; }
    public function delete(User $user, DailyBooking $booking): bool { return $user->exists; }
}
