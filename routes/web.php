<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Core\AuditLogController;
use App\Http\Controllers\Core\NotificationController;
use App\Http\Controllers\Core\SettingsController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\Md\ItemCategoryController;
use App\Http\Controllers\Md\ItemController;
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
    });

    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
});
