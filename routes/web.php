<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DashboardWidgetSettingController;
use App\Http\Controllers\Admin\MenuSettingController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SocietyAdminController;
use App\Http\Controllers\Admin\SocietyController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Society\AnnouncementController;
use App\Http\Controllers\Society\ComplaintController;
use App\Http\Controllers\Society\DirectoryController;
use App\Http\Controllers\Society\ElectionController;
use App\Http\Controllers\Society\EventController;
use App\Http\Controllers\Society\MaintenanceController;
use App\Http\Controllers\Society\PaymentController;
use App\Http\Controllers\Society\WaterReadingController;
use App\Http\Controllers\Society\SocietyAuthController;
use App\Http\Controllers\Society\SocietyDashboardController;
use App\Http\Controllers\Society\VisitorController;
use Illuminate\Support\Facades\Route;

// Public landing page.
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Guest-only auth routes (login/register). Throttled at the route level as a
// coarse network-wide backstop; the fine-grained per-email+IP lockout lives
// in LoginRequest::ensureIsNotRateLimited().
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Email verification (required because App\Models\User implements MustVerifyEmail).
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Admin panel — requires an authenticated, verified session AND role=admin.
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Module routes (stub placeholders for now)
    Route::get('/modules/maintenance', function () {
        return view('admin.modules.maintenance');
    })->name('modules.maintenance');
    
    Route::get('/modules/visitor', function () {
        return view('admin.modules.visitor');
    })->name('modules.visitor');
    
    Route::get('/modules/complaint', function () {
        return view('admin.modules.complaint');
    })->name('modules.complaint');
    
    Route::get('/modules/election', function () {
        return view('admin.modules.election');
    })->name('modules.election');
    
    Route::get('/modules/announcement', function () {
        return view('admin.modules.announcement');
    })->name('modules.announcement');
    
    Route::get('/modules/payment', function () {
        return view('admin.modules.payment');
    })->name('modules.payment');
    
    Route::get('/modules/directory', function () {
        return view('admin.modules.directory');
    })->name('modules.directory');
    
    Route::get('/modules/event', function () {
        return view('admin.modules.event');
    })->name('modules.event');
    
    // Society CRUD + provisioning
    Route::get('/societies', [SocietyController::class, 'index'])->name('societies.index');
    Route::get('/societies/create', [SocietyController::class, 'create'])->name('societies.create');
    Route::post('/societies', [SocietyController::class, 'store'])->name('societies.store');
    Route::get('/societies/{id}', [SocietyController::class, 'show'])->name('societies.show');
    Route::get('/societies/{id}/edit', [SocietyController::class, 'edit'])->name('societies.edit');
    Route::put('/societies/{id}', [SocietyController::class, 'update'])->name('societies.update');
    Route::delete('/societies/{id}', [SocietyController::class, 'destroy'])->name('societies.destroy');
    Route::post('/societies/{id}/modules/{moduleId}/toggle', [SocietyController::class, 'toggleModule'])->name('societies.modules.toggle');
    Route::post('/societies/{id}/retry-provisioning', [SocietyController::class, 'retryProvisioning'])->name('societies.retry_provisioning');

    // Society admin management
    Route::get('/societies/{id}/admins', [SocietyAdminController::class, 'index'])->name('societies.admins.index');
    Route::get('/societies/{id}/admins/create', [SocietyAdminController::class, 'create'])->name('societies.admins.create');
    Route::post('/societies/{id}/admins', [SocietyAdminController::class, 'store'])->name('societies.admins.store');
    Route::get('/societies/{id}/admins/{adminId}/edit', [SocietyAdminController::class, 'edit'])->name('societies.admins.edit');
    Route::put('/societies/{id}/admins/{adminId}', [SocietyAdminController::class, 'update'])->name('societies.admins.update');
    Route::post('/societies/{id}/admins/{adminId}/activate', [SocietyAdminController::class, 'activate'])->name('societies.admins.activate');
    Route::post('/societies/{id}/admins/{adminId}/deactivate', [SocietyAdminController::class, 'deactivate'])->name('societies.admins.deactivate');
    Route::delete('/societies/{id}/admins/{adminId}', [SocietyAdminController::class, 'destroy'])->name('societies.admins.destroy');
    
    Route::get('/super-admins', function () {
        return view('admin.super_admins.index');
    })->name('super_admins.index');
    
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit_logs.index');

    // Platform-wide settings (super admin only)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/branding', [PlatformSettingController::class, 'edit'])->name('branding.edit');
        Route::post('/branding', [PlatformSettingController::class, 'update'])->name('branding.update');
        Route::delete('/branding', [PlatformSettingController::class, 'destroy'])->name('branding.destroy');

        // Role catalog (used to provision new societies + sync into existing ones)
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::post('/roles/sync', [RoleController::class, 'sync'])->name('roles.sync');

        // Per-role society-portal sidebar visibility
        Route::get('/menu', [MenuSettingController::class, 'edit'])->name('menu.edit');
        Route::post('/menu', [MenuSettingController::class, 'update'])->name('menu.update');

        // Per-role admin-dashboard widget visibility
        Route::get('/dashboard-widgets', [DashboardWidgetSettingController::class, 'edit'])->name('dashboard_widgets.edit');
        Route::post('/dashboard-widgets', [DashboardWidgetSettingController::class, 'update'])->name('dashboard_widgets.update');
    });
});

// Society (tenant) portal — separate auth guard/session/database from the
// super-admin panel above. SetSocietyContext resolves the tenant database
// from the session AND performs the 'society' guard's auth check itself
// (see the middleware's docblock for why it's not split into a separate
// 'auth:society' middleware).
Route::prefix('society')->name('society.')->group(function () {
    Route::get('/login', [SocietyAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [SocietyAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/logout', [SocietyAuthController::class, 'logout'])->name('logout');

    Route::middleware(['society.context'])->group(function () {
        Route::get('/dashboard', [SocietyDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MaintenanceController::class, 'index'])->name('index');
            Route::get('/create', [MaintenanceController::class, 'create'])->name('create');
            Route::post('/', [MaintenanceController::class, 'store'])->name('store');
            Route::get('/{id}', [MaintenanceController::class, 'show'])->name('show');
            Route::put('/{id}', [MaintenanceController::class, 'update'])->name('update');
        });

        Route::prefix('visitors')->name('visitors.')->group(function () {
            Route::get('/', [VisitorController::class, 'index'])->name('index');
            Route::get('/create', [VisitorController::class, 'create'])->name('create');
            Route::post('/', [VisitorController::class, 'store'])->name('store');
            Route::post('/{id}/check-out', [VisitorController::class, 'checkOut'])->name('check_out');
        });

        Route::prefix('complaints')->name('complaints.')->group(function () {
            Route::get('/', [ComplaintController::class, 'index'])->name('index');
            Route::get('/create', [ComplaintController::class, 'create'])->name('create');
            Route::post('/', [ComplaintController::class, 'store'])->name('store');
            Route::get('/{id}', [ComplaintController::class, 'show'])->name('show');
            Route::put('/{id}', [ComplaintController::class, 'update'])->name('update');
        });

        Route::prefix('directory')->name('directory.')->group(function () {
            Route::get('/', [DirectoryController::class, 'index'])->name('index');
            Route::get('/create', [DirectoryController::class, 'create'])->name('create');
            Route::post('/', [DirectoryController::class, 'store'])->name('store');
            Route::get('/{userId}', [DirectoryController::class, 'show'])->name('show');
        });

        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
            Route::post('/', [AnnouncementController::class, 'store'])->name('store');
            Route::get('/{id}', [AnnouncementController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [AnnouncementController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AnnouncementController::class, 'update'])->name('update');
            Route::delete('/{id}', [AnnouncementController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [EventController::class, 'index'])->name('index');
            Route::get('/create', [EventController::class, 'create'])->name('create');
            Route::post('/', [EventController::class, 'store'])->name('store');
            Route::get('/{id}', [EventController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [EventController::class, 'edit'])->name('edit');
            Route::put('/{id}', [EventController::class, 'update'])->name('update');
            Route::delete('/{id}', [EventController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('elections')->name('elections.')->group(function () {
            Route::get('/', [ElectionController::class, 'index'])->name('index');
            Route::get('/create', [ElectionController::class, 'create'])->name('create');
            Route::post('/', [ElectionController::class, 'store'])->name('store');
            Route::get('/{id}', [ElectionController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ElectionController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ElectionController::class, 'update'])->name('update');
            Route::post('/{id}/status', [ElectionController::class, 'updateStatus'])->name('status');
            Route::post('/{id}/candidates', [ElectionController::class, 'addCandidate'])->name('candidates.store');
            Route::delete('/{id}/candidates/{candidateId}', [ElectionController::class, 'removeCandidate'])->name('candidates.destroy');
            Route::post('/{id}/vote', [ElectionController::class, 'vote'])->name('vote');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/create', [PaymentController::class, 'create'])->name('create');
            Route::post('/', [PaymentController::class, 'store'])->name('store');
            Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
            Route::post('/{id}/pay', [PaymentController::class, 'recordPayment'])->name('pay');
        });

        Route::prefix('water-readings')->name('water-readings.')->group(function () {
            Route::get('/', [WaterReadingController::class, 'index'])->name('index');
            Route::get('/create', [WaterReadingController::class, 'create'])->name('create');
            Route::post('/', [WaterReadingController::class, 'store'])->name('store');
        });
    });
});
