<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\InsuranceClaimController;
use App\Http\Controllers\Api\InsuranceDashboardController;
use App\Http\Controllers\Api\InsuranceDocumentController;
use App\Http\Controllers\Api\InsurancePolicyController;
use App\Http\Controllers\Api\InsuranceProviderController;
use App\Http\Controllers\Api\DailyBookingController;
use App\Http\Controllers\Api\MonthlyContractController;
use Illuminate\Support\Facades\Route;

// Only the session guard is accepted: legacy bearer tokens cannot authenticate.
Route::middleware('auth:web')->group(function (): void {
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/daily-bookings/options', [DailyBookingController::class, 'options']);
    Route::apiResource('daily-bookings', DailyBookingController::class)->parameters(['daily-bookings' => 'dailyBooking']);

    Route::get('/monthly-contracts/options', [MonthlyContractController::class, 'options']);
    Route::get('/monthly-contracts/summary', [MonthlyContractController::class, 'summary']);
    Route::get('/monthly-contracts/export.csv', [MonthlyContractController::class, 'exportCsv']);
    Route::post('/monthly-contracts/{monthlyContract}/renew', [MonthlyContractController::class, 'renew']);
    Route::apiResource('monthly-contracts', MonthlyContractController::class)->parameters(['monthly-contracts' => 'monthlyContract']);

    Route::prefix('insurance')->name('insurance.')->group(function (): void {
        Route::get('/dashboard', InsuranceDashboardController::class)->name('dashboard');
        Route::get('/vehicles', [InsurancePolicyController::class, 'vehicles'])->name('vehicles');
        Route::apiResource('providers', InsuranceProviderController::class);
        Route::get('/policies', [InsurancePolicyController::class, 'index'])->name('policies.index');
        Route::post('/policies', [InsurancePolicyController::class, 'store'])->name('policies.store');
        Route::get('/policies/{policy}', [InsurancePolicyController::class, 'show'])->name('policies.show');
        Route::put('/policies/{policy}', [InsurancePolicyController::class, 'update'])->name('policies.update');
        Route::post('/policies/{policy}/renew', [InsurancePolicyController::class, 'renew'])->name('policies.renew');
        Route::post('/policies/{policy}/cancel', [InsurancePolicyController::class, 'cancel'])->name('policies.cancel');
        Route::post('/policies/{policy}/archive', [InsurancePolicyController::class, 'archive'])->name('policies.archive');
        Route::post('/policies/{policy}/documents', [InsuranceDocumentController::class, 'store'])->name('documents.store');
        Route::post('/documents/{document}/archive', [InsuranceDocumentController::class, 'archive'])->name('documents.archive');
        Route::get('/documents/{document}/download', [InsuranceDocumentController::class, 'download'])->name('documents.download');
        Route::apiResource('claims', InsuranceClaimController::class)->only(['index', 'store', 'show', 'update']);
    });
});
Route::get('/test', fn () => response()->json(['status' => 'success', 'message' => 'GSS Fleet Management API is running', 'version' => '2.0']));
