<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->is_active || $user->role_id === null) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_PENDING_APPROVAL',
                'message' => 'Your account is pending administrator approval. Contact an administrator.',
                'errors' => (object) [],
            ], 403);
        }

        $parts = explode('/', trim($request->path(), '/'));
        $module = $parts[1] ?? null;
        $permissionModule = match ($module) {
            'dashboard' => 'dashboard',
            'daily-bookings' => 'bookings',
            'monthly-contracts' => 'contracts',
            'cars' => 'fleet',
            'car-licenses', 'car-license-documents' => 'car_licenses',
            'customers' => 'customers',
            'drivers' => 'drivers',
            'operators' => 'operators',
            'maintenances' => 'maintenance',
            'insurance' => 'insurance',
            'payments' => 'payments',
            'reports' => 'reports',
            default => null,
        };
        if ($permissionModule !== null) {
            $action = $request->isMethod('GET') || $request->isMethod('HEAD') ? 'view' : 'manage';
            abort_unless($user->hasPermission($permissionModule.'.'.$action)
                || ($action === 'view' && $user->hasPermission($permissionModule.'.manage')),
                403, 'Your role does not have access to this module.');
        }

        return $next($request);
    }
}
