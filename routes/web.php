<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Core\AuditLogController;
use App\Http\Controllers\Core\NotificationController;
use App\Http\Controllers\Core\SettingsController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\Md\DoctorController;
use App\Http\Controllers\Md\HealthServiceController;
use App\Http\Controllers\Md\ItemCategoryController;
use App\Http\Controllers\Md\ItemController;
use App\Http\Controllers\Md\PatientController;
use App\Http\Controllers\Md\PharmacyStaffController;
use App\Http\Controllers\Md\SupplierController;
use App\Http\Controllers\Md\UnitController;
use App\Http\Middleware\EnsureActiveSession;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:login')
    ->name('login.store');

Route::middleware(['auth', EnsureActiveSession::class])->group(function (): void {
    Route::get('/', fn () => view('welcome'))->name('home');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('can:core.audit.view')
        ->name('audit-logs.index');

    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('can:core.settings.view')
        ->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])
        ->middleware('can:core.settings.update')
        ->name('settings.update');

    Route::prefix('master')->name('md.')->group(function (): void {
        Route::get('/items', [ItemController::class, 'index'])->middleware('can:md.item.view')->name('items.index');
        Route::get('/items/create', [ItemController::class, 'create'])->middleware('can:md.item.manage')->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->middleware('can:md.item.manage')->name('items.store');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->middleware('can:md.item.manage')->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->middleware('can:md.item.manage')->name('items.update');
        Route::patch('/items/{item}/status', [ItemController::class, 'updateStatus'])->middleware('can:md.item.manage')->name('items.status');

        Route::get('/categories', [ItemCategoryController::class, 'index'])->middleware('can:md.item.manage')->name('categories.index');
        Route::post('/categories', [ItemCategoryController::class, 'store'])->middleware('can:md.item.manage')->name('categories.store');
        Route::get('/categories/{category}/edit', [ItemCategoryController::class, 'edit'])->middleware('can:md.item.manage')->name('categories.edit');
        Route::put('/categories/{category}', [ItemCategoryController::class, 'update'])->middleware('can:md.item.manage')->name('categories.update');
        Route::patch('/categories/{category}/status', [ItemCategoryController::class, 'updateStatus'])->middleware('can:md.item.manage')->name('categories.status');

        Route::get('/units', [UnitController::class, 'index'])->middleware('can:md.item.manage')->name('units.index');
        Route::post('/units', [UnitController::class, 'store'])->middleware('can:md.item.manage')->name('units.store');
        Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->middleware('can:md.item.manage')->name('units.edit');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->middleware('can:md.item.manage')->name('units.update');
        Route::patch('/units/{unit}/status', [UnitController::class, 'updateStatus'])->middleware('can:md.item.manage')->name('units.status');

        Route::get('/suppliers', [SupplierController::class, 'index'])->middleware('can:md.item.view')->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->middleware('can:md.item.manage')->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('can:md.item.manage')->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->middleware('can:md.item.manage')->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('can:md.item.manage')->name('suppliers.update');
        Route::patch('/suppliers/{supplier}/status', [SupplierController::class, 'updateStatus'])->middleware('can:md.item.manage')->name('suppliers.status');

        Route::get('/patients', [PatientController::class, 'index'])->middleware('can:md.item.view')->name('patients.index');
        Route::get('/patients/create', [PatientController::class, 'create'])->middleware('can:md.item.manage')->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->middleware('can:md.item.manage')->name('patients.store');
        Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->middleware('can:md.item.manage')->name('patients.edit');
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->middleware('can:md.item.manage')->name('patients.update');
        Route::patch('/patients/{patient}/status', [PatientController::class, 'updateStatus'])->middleware('can:md.item.manage')->name('patients.status');

        Route::get('/doctors', [DoctorController::class, 'index'])->middleware('can:md.item.view')->name('doctors.index');
        Route::get('/doctors/create', [DoctorController::class, 'create'])->middleware('can:md.item.manage')->name('doctors.create');
        Route::post('/doctors', [DoctorController::class, 'store'])->middleware('can:md.item.manage')->name('doctors.store');
        Route::get('/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->middleware('can:md.item.manage')->name('doctors.edit');
        Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->middleware('can:md.item.manage')->name('doctors.update');
        Route::patch('/doctors/{doctor}/status', [DoctorController::class, 'updateStatus'])->middleware('can:md.item.manage')->name('doctors.status');

        Route::get('/pharmacy-staff', [PharmacyStaffController::class, 'index'])->middleware('can:md.item.view')->name('pharmacy-staff.index');
        Route::get('/pharmacy-staff/create', [PharmacyStaffController::class, 'create'])->middleware('can:md.item.manage')->name('pharmacy-staff.create');
        Route::post('/pharmacy-staff', [PharmacyStaffController::class, 'store'])->middleware('can:md.item.manage')->name('pharmacy-staff.store');
        Route::get('/pharmacy-staff/{pharmacyStaff}/edit', [PharmacyStaffController::class, 'edit'])->middleware('can:md.item.manage')->name('pharmacy-staff.edit');
        Route::put('/pharmacy-staff/{pharmacyStaff}', [PharmacyStaffController::class, 'update'])->middleware('can:md.item.manage')->name('pharmacy-staff.update');
        Route::patch('/pharmacy-staff/{pharmacyStaff}/status', [PharmacyStaffController::class, 'updateStatus'])->middleware('can:md.item.manage')->name('pharmacy-staff.status');

        Route::get('/health-services', [HealthServiceController::class, 'index'])->middleware('can:md.item.view')->name('health-services.index');
        Route::get('/health-services/create', [HealthServiceController::class, 'create'])->middleware('can:md.item.manage')->name('health-services.create');
        Route::post('/health-services', [HealthServiceController::class, 'store'])->middleware('can:md.item.manage')->name('health-services.store');
        Route::get('/health-services/{healthService}/edit', [HealthServiceController::class, 'edit'])->middleware('can:md.item.manage')->name('health-services.edit');
        Route::put('/health-services/{healthService}', [HealthServiceController::class, 'update'])->middleware('can:md.item.manage')->name('health-services.update');
        Route::patch('/health-services/{healthService}/status', [HealthServiceController::class, 'updateStatus'])->middleware('can:md.item.manage')->name('health-services.status');
    });

    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
});
