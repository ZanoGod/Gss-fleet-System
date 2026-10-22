<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user()->only(['id', 'name', 'username']));
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::get('/test', fn () => response()->json(['status' => 'success', 'message' => 'GSS Fleet Management API is running', 'version' => '2.0']));
