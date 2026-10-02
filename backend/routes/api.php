<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CronController;
use App\Http\Controllers\Api\V1\Customer\AddressController;
use App\Http\Controllers\Api\V1\Customer\BookingController;
use App\Http\Controllers\Api\V1\Customer\CatalogController;
use App\Http\Controllers\Api\V1\Customer\ComplaintController;
use App\Http\Controllers\Api\V1\Customer\ContractController;
use App\Http\Controllers\Api\V1\Worker\WorkerController;
use App\Http\Middleware\EnsureIdempotency;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| لمعة API v1 — المسار الأساسي /api/v1 (انظر docs/03_phase3_api.md)
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------------- public
Route::middleware('throttle:auth')->prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware('throttle:api')->group(function () {
    Route::get('services', [CatalogController::class, 'services'])->name('services.index');
    Route::get('services/{id}', [CatalogController::class, 'service'])->whereNumber('id')->name('services.show');
    Route::get('contract-plans', [CatalogController::class, 'plans'])->name('plans.index');
});

// المهمة اليومية لاستضافة بدون cron (رمز سري — انظر CronController)
Route::post('internal/cron/daily', [CronController::class, 'daily'])->middleware('throttle:6,1')->name('cron.daily');

// ------------------------------------------------------- authenticated
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // مشترك (عميل + عاملة)
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::patch('auth/me', [AuthController::class, 'updateProfile'])->name('auth.update');
    Route::post('auth/password', [AuthController::class, 'changePassword'])->name('auth.password');

    Route::get('notifications', [AccountController::class, 'notifications'])->name('notifications.index');
    Route::post('notifications/read-all', [AccountController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{id}/read', [AccountController::class, 'markRead'])->name('notifications.read');
    Route::post('device-tokens', [AccountController::class, 'storeDeviceToken'])->name('device-tokens.store');
    Route::delete('device-tokens', [AccountController::class, 'destroyDeviceToken'])->name('device-tokens.destroy');

    // ------------------------------------------------------------ customer
    Route::middleware('role:customer')->name('customer.')->group(function () {
        Route::get('availability', [CatalogController::class, 'availability'])->name('availability');

        Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('addresses/{id}', [AddressController::class, 'update'])->whereNumber('id')->name('addresses.update');
        Route::delete('addresses/{id}', [AddressController::class, 'destroy'])->whereNumber('id')->name('addresses.destroy');

        Route::post('bookings/quote', [BookingController::class, 'quote'])->name('bookings.quote');
        Route::post('bookings', [BookingController::class, 'store'])->middleware(EnsureIdempotency::class)->name('bookings.store');
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{id}', [BookingController::class, 'show'])->whereNumber('id')->name('bookings.show');
        Route::post('bookings/{id}/cancel', [BookingController::class, 'cancel'])->whereNumber('id')->name('bookings.cancel');
        Route::post('bookings/{id}/rating', [BookingController::class, 'rate'])->whereNumber('id')->name('bookings.rate');

        Route::post('contracts/quote', [ContractController::class, 'quote'])->name('contracts.quote');
        Route::post('contracts', [ContractController::class, 'store'])->middleware(EnsureIdempotency::class)->name('contracts.store');
        Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
        Route::get('contracts/{id}', [ContractController::class, 'show'])->whereNumber('id')->name('contracts.show');
        Route::post('contracts/{id}/cancel', [ContractController::class, 'cancel'])->whereNumber('id')->name('contracts.cancel');
        Route::post('contracts/{id}/rating', [ContractController::class, 'rate'])->whereNumber('id')->name('contracts.rate');
        Route::get('contracts/{id}/change-requests', [ContractController::class, 'changeRequests'])->whereNumber('id')->name('contracts.change-requests.index');
        Route::post('contracts/{id}/change-requests', [ContractController::class, 'storeChangeRequest'])->whereNumber('id')->name('contracts.change-requests.store');

        Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
        Route::post('complaints', [ComplaintController::class, 'store'])->name('complaints.store');
        Route::get('complaints/{id}', [ComplaintController::class, 'show'])->whereNumber('id')->name('complaints.show');
        Route::post('complaints/{id}/messages', [ComplaintController::class, 'message'])->whereNumber('id')->name('complaints.messages');
    });

    // -------------------------------------------------------------- worker
    Route::middleware('role:worker')->prefix('worker')->name('worker.')->group(function () {
        Route::get('ratings', [WorkerController::class, 'ratings'])->name('ratings.index');

        // CR-3: فرق الزيارات — الإجراءات لقائد الفريق فقط (يُفرض في BookingService)
        Route::middleware('worker.type:cleaner')->group(function () {
            Route::get('bookings', [WorkerController::class, 'bookings'])->name('bookings.index');
            Route::get('bookings/{id}', [WorkerController::class, 'booking'])->whereNumber('id')->name('bookings.show');
            Route::post('bookings/{id}/accept', [WorkerController::class, 'accept'])->whereNumber('id')->name('bookings.accept');
            Route::post('bookings/{id}/reject', [WorkerController::class, 'reject'])->whereNumber('id')->name('bookings.reject');
            Route::post('bookings/{id}/status', [WorkerController::class, 'updateStatus'])->whereNumber('id')->name('bookings.status');
        });

        // الخادمات — العقود فقط
        Route::middleware('worker.type:housekeeper')->group(function () {
            Route::get('contracts', [WorkerController::class, 'contracts'])->name('contracts.index');
            Route::get('contracts/{id}', [WorkerController::class, 'contract'])->whereNumber('id')->name('contracts.show');
        });
    });
});
