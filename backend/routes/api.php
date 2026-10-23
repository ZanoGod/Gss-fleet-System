<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Only the session guard is accepted: legacy bearer tokens cannot authenticate.
Route::middleware('auth:web')->group(function (): void {
    Route::get('/user', [AuthController::class, 'user']);
});
Route::get('/test', fn () => response()->json(['status' => 'success', 'message' => 'GSS Fleet Management API is running', 'version' => '2.0']));
