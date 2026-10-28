<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

Route::post(
    '/login',
    [AuthController::class, 'login']
)->middleware('throttle:5,1');

Route::middleware('auth')->post(
    '/logout',
    [AuthController::class, 'logout']
);
