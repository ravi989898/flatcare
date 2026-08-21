<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Common\AnnouncementController;
use App\Http\Controllers\Api\V1\Common\DirectoryController;
use App\Http\Controllers\Api\V1\Common\EventController;
use App\Http\Controllers\Api\V1\Resident\BillController;
use App\Http\Controllers\Api\V1\Resident\ComplaintController;
use App\Http\Controllers\Api\V1\Resident\FamilyMemberController;
use App\Http\Controllers\Api\V1\Resident\MaintenanceRequestController;
use App\Http\Controllers\Api\V1\Resident\ProfileController;
use App\Http\Controllers\Api\V1\Resident\VehicleController;
use App\Http\Controllers\Api\V1\Resident\VisitorController;
use Illuminate\Support\Facades\Route;

// Mobile app REST API — see ARCHITECTURE.md §7 for the overall shape and
// AuthenticateApiToken for how a Bearer token resolves to a tenant + user.
Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('forgot_password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('reset_password');
    });

    Route::middleware('api.auth')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::put('/', [ProfileController::class, 'update'])->name('update');

            Route::get('/family-members', [FamilyMemberController::class, 'index'])->name('family_members.index');
            Route::post('/family-members', [FamilyMemberController::class, 'store'])->name('family_members.store');
            Route::put('/family-members/{id}', [FamilyMemberController::class, 'update'])->name('family_members.update');
            Route::delete('/family-members/{id}', [FamilyMemberController::class, 'destroy'])->name('family_members.destroy');

            Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
            Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
            Route::put('/vehicles/{id}', [VehicleController::class, 'update'])->name('vehicles.update');
            Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
        });

        Route::prefix('maintenance-requests')->name('maintenance_requests.')->group(function () {
            Route::get('/', [MaintenanceRequestController::class, 'index'])->name('index');
            Route::post('/', [MaintenanceRequestController::class, 'store'])->name('store');
            Route::get('/{id}', [MaintenanceRequestController::class, 'show'])->name('show');
        });

        Route::prefix('bills')->name('bills.')->group(function () {
            Route::get('/', [BillController::class, 'index'])->name('index');
            Route::get('/{id}', [BillController::class, 'show'])->name('show');
        });

        Route::prefix('complaints')->name('complaints.')->group(function () {
            Route::get('/', [ComplaintController::class, 'index'])->name('index');
            Route::post('/', [ComplaintController::class, 'store'])->name('store');
            Route::get('/{id}', [ComplaintController::class, 'show'])->name('show');
        });

        Route::prefix('visitors')->name('visitors.')->group(function () {
            Route::get('/', [VisitorController::class, 'index'])->name('index');
            Route::get('/{id}', [VisitorController::class, 'show'])->name('show');
        });

        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::get('/{id}', [AnnouncementController::class, 'show'])->name('show');
        });

        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [EventController::class, 'index'])->name('index');
            Route::get('/{id}', [EventController::class, 'show'])->name('show');
        });

        Route::prefix('directory')->name('directory.')->group(function () {
            Route::get('/', [DirectoryController::class, 'index'])->name('index');
            Route::get('/{userId}', [DirectoryController::class, 'show'])->name('show');
        });
    });
});
