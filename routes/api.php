<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\JobCardController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API V1 Routes
Route::prefix('v1')->group(function () {

    // Fallback Login Route to prevent "Route [login] not defined" 500 errors
    Route::get('login', function () {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
            'error_code' => 'UNAUTHENTICATED',
        ], 401);
    })->name('login');

    // Authentication Routes (Public)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('register', [AuthController::class, 'register'])->name('api.v1.auth.register');

        // Protected Auth Routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('api.v1.auth.refresh');
            Route::get('me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        });
    });

    // Protected API Routes
    Route::middleware(['auth:sanctum'])->group(function () {

        // Customer Routes
        Route::apiResource('customers', CustomerController::class);

        // Vehicle Routes
        Route::apiResource('vehicles', VehicleController::class);

        // Job Card Routes
        Route::prefix('job-cards')->name('job-cards.')->group(function () {
            Route::get('/', [JobCardController::class, 'index'])->name('index');
            Route::post('/', [JobCardController::class, 'store'])->name('store');
            Route::get('{jobCard}', [JobCardController::class, 'show'])->name('show');
            Route::put('{jobCard}', [JobCardController::class, 'update'])->name('update');
            Route::delete('{jobCard}', [JobCardController::class, 'destroy'])->name('destroy');
            Route::post('{jobCard}/items', [JobCardController::class, 'addItem'])->name('add-item');
            Route::patch('{jobCard}/status', [JobCardController::class, 'updateStatus'])->name('update-status');
        });

        // Invoice Routes
        Route::apiResource('invoices', InvoiceController::class);

        // Payment Routes
        Route::apiResource('payments', PaymentController::class);

        // Appointment Routes
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [AppointmentController::class, 'index'])->name('index');
            Route::post('/', [AppointmentController::class, 'store'])->name('store');
            Route::get('{appointment}', [AppointmentController::class, 'show'])->name('show');
            Route::put('{appointment}', [AppointmentController::class, 'update'])->name('update');
            Route::delete('{appointment}', [AppointmentController::class, 'destroy'])->name('destroy');
            Route::post('{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('confirm');
        });

        // Dashboard Stats (placeholder for future implementation)
        Route::get('dashboard/stats', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_customers' => 0,
                    'active_job_cards' => 0,
                    'pending_invoices' => 0,
                    'today_appointments' => 0,
                ],
                'message' => 'Dashboard statistics retrieved successfully',
            ]);
        })->name('dashboard.stats');
    });
});
