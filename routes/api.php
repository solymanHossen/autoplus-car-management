<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\JobCardController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TaxRateController;
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
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('api.v1.auth.login');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('api.v1.auth.register');

        // Protected Auth Routes
        Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('api.v1.auth.refresh');
            Route::get('me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        });
    });

    // Protected API Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // Customer Routes (RBAC)
        Route::apiResource('customers', CustomerController::class)->only(['index', 'show'])->middleware('permission:view-customers');
        Route::apiResource('customers', CustomerController::class)->only(['store'])->middleware('permission:create-customers');
        Route::apiResource('customers', CustomerController::class)->only(['update'])->middleware('permission:edit-customers');
        Route::apiResource('customers', CustomerController::class)->only(['destroy'])->middleware('permission:delete-customers');

        // Vehicle Routes (RBAC)
        Route::apiResource('vehicles', VehicleController::class)->only(['index', 'show'])->middleware('permission:view-vehicles');
        Route::apiResource('vehicles', VehicleController::class)->only(['store'])->middleware('permission:create-vehicles');
        Route::apiResource('vehicles', VehicleController::class)->only(['update'])->middleware('permission:edit-vehicles');
        Route::apiResource('vehicles', VehicleController::class)->only(['destroy'])->middleware('permission:delete-vehicles');

        // Supplier Routes (Inventory RBAC)
        Route::apiResource('suppliers', SupplierController::class)->only(['index', 'show'])->middleware('permission:view-inventory');
        Route::apiResource('suppliers', SupplierController::class)->only(['store'])->middleware('permission:create-inventory');
        Route::apiResource('suppliers', SupplierController::class)->only(['update'])->middleware('permission:edit-inventory');
        Route::apiResource('suppliers', SupplierController::class)->only(['destroy'])->middleware('permission:delete-inventory');

        // Product Routes (Inventory RBAC)
        Route::apiResource('products', ProductController::class)->only(['index', 'show'])->middleware('permission:view-inventory');
        Route::apiResource('products', ProductController::class)->only(['store'])->middleware('permission:create-inventory');
        Route::apiResource('products', ProductController::class)->only(['update'])->middleware('permission:edit-inventory');
        Route::apiResource('products', ProductController::class)->only(['destroy'])->middleware('permission:delete-inventory');

        // Tax Rate Routes (Inventory RBAC)
        Route::apiResource('tax-rates', TaxRateController::class)->only(['index', 'show'])->middleware('permission:view-inventory');
        Route::apiResource('tax-rates', TaxRateController::class)->only(['store'])->middleware('permission:create-inventory');
        Route::apiResource('tax-rates', TaxRateController::class)->only(['update'])->middleware('permission:edit-inventory');
        Route::apiResource('tax-rates', TaxRateController::class)->only(['destroy'])->middleware('permission:delete-inventory');

        // Job Card Routes
        Route::prefix('job-cards')->name('job-cards.')->group(function () {
            Route::get('/', [JobCardController::class, 'index'])->middleware('permission:view-job-cards')->name('index');
            Route::post('/', [JobCardController::class, 'store'])->middleware('permission:create-job-cards')->name('store');
            Route::get('{jobCard}', [JobCardController::class, 'show'])->middleware('permission:view-job-cards')->name('show');
            Route::put('{jobCard}', [JobCardController::class, 'update'])->middleware('permission:edit-job-cards')->name('update');
            Route::delete('{jobCard}', [JobCardController::class, 'destroy'])->middleware('permission:delete-job-cards')->name('destroy');
            Route::post('{jobCard}/items', [JobCardController::class, 'addItem'])->middleware('permission:edit-job-cards')->name('add-item');
            Route::patch('{jobCard}/status', [JobCardController::class, 'updateStatus'])->middleware('permission:edit-job-cards')->name('update-status');
        });

        // Invoice Routes (RBAC)
        Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show'])->middleware('permission:view-invoices');
        Route::apiResource('invoices', InvoiceController::class)->only(['store'])->middleware('permission:create-invoices');
        Route::apiResource('invoices', InvoiceController::class)->only(['update'])->middleware('permission:edit-invoices');
        Route::apiResource('invoices', InvoiceController::class)->only(['destroy'])->middleware('permission:delete-invoices');

        // Payment Routes (RBAC)
        Route::apiResource('payments', PaymentController::class)->only(['index', 'show'])->middleware('permission:view-payments');
        Route::apiResource('payments', PaymentController::class)->only(['store'])->middleware('permission:create-payments');
        Route::apiResource('payments', PaymentController::class)->only(['update'])->middleware('permission:edit-payments');
        Route::apiResource('payments', PaymentController::class)->only(['destroy'])->middleware('permission:delete-payments');

        // Appointment Routes
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [AppointmentController::class, 'index'])->middleware('permission:view-appointments')->name('index');
            Route::post('/', [AppointmentController::class, 'store'])->middleware('permission:create-appointments')->name('store');
            Route::get('{appointment}', [AppointmentController::class, 'show'])->middleware('permission:view-appointments')->name('show');
            Route::put('{appointment}', [AppointmentController::class, 'update'])->middleware('permission:edit-appointments')->name('update');
            Route::delete('{appointment}', [AppointmentController::class, 'destroy'])->middleware('permission:delete-appointments')->name('destroy');
            Route::post('{appointment}/confirm', [AppointmentController::class, 'confirm'])->middleware('permission:confirm-appointments')->name('confirm');
        });

        // Attachment Uploads
        Route::get('attachments', [AttachmentController::class, 'index'])
            ->middleware('permission:view-job-cards')
            ->name('attachments.index');

        Route::post('attachments', [AttachmentController::class, 'store'])
            ->middleware('permission:edit-job-cards')
            ->name('attachments.store');

        Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])
            ->middleware('permission:view-job-cards')
            ->name('attachments.show');

        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
            ->middleware('permission:edit-job-cards')
            ->name('attachments.destroy');

        // Dashboard Stats
        Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    });
});
