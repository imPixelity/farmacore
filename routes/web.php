<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Core\AuditLogController;
use App\Http\Controllers\Core\NotificationController;
use App\Http\Controllers\Core\SettingsController;
use App\Http\Controllers\Core\UserController;
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

    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
});
