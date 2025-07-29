<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BusController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\MonitoringController;

// Admin Authentication Routes (Guest only)
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');
});

// Admin Protected Routes
Route::middleware('auth:admin')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    // Bus Management (Admin role required)
    Route::middleware('can:manage-buses')->group(function () {
        Route::resource('buses', BusController::class)->names([
            'index' => 'admin.buses.index',
            'create' => 'admin.buses.create',
            'store' => 'admin.buses.store',
            'show' => 'admin.buses.show',
            'edit' => 'admin.buses.edit',
            'update' => 'admin.buses.update',
            'destroy' => 'admin.buses.destroy'
        ]);
        Route::patch('buses/{bus}/toggle-status', [BusController::class, 'toggleStatus'])->name('admin.buses.toggle-status');
    });

    // Schedule Management (Admin role required)
    Route::middleware('can:manage-schedules')->group(function () {
        Route::resource('schedules', ScheduleController::class)->names([
            'index' => 'admin.schedules.index',
            'create' => 'admin.schedules.create',
            'store' => 'admin.schedules.store',
            'show' => 'admin.schedules.show',
            'edit' => 'admin.schedules.edit',
            'update' => 'admin.schedules.update',
            'destroy' => 'admin.schedules.destroy'
        ]);
        Route::get('schedules/{schedule}/routes', [ScheduleController::class, 'manageRoutes'])->name('admin.schedules.routes');
        Route::post('schedules/{schedule}/routes', [ScheduleController::class, 'storeRoute'])->name('admin.schedules.routes.store');
        Route::delete('schedules/{schedule}/routes/{route}', [ScheduleController::class, 'destroyRoute'])->name('admin.schedules.routes.destroy');
        Route::get('schedules/check-conflicts', [ScheduleController::class, 'checkConflicts'])->name('admin.schedules.check-conflicts');
        Route::get('schedules/bulk-create', [ScheduleController::class, 'bulkCreate'])->name('admin.schedules.bulk-create');
        Route::post('schedules/bulk-store', [ScheduleController::class, 'bulkStore'])->name('admin.schedules.bulk-store');
        Route::get('schedules/templates', [ScheduleController::class, 'templates'])->name('admin.schedules.templates');
        Route::post('schedules/templates', [ScheduleController::class, 'storeTemplate'])->name('admin.schedules.templates.store');
        Route::get('schedules/{schedule}/history', [ScheduleController::class, 'history'])->name('admin.schedules.history');
    });

    // Business Settings (Super Admin only)
    Route::middleware('can:manage-settings')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('admin.settings.update');
        Route::post('settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('admin.settings.upload-logo');
        Route::post('settings/backup', [SettingsController::class, 'backup'])->name('admin.settings.backup');
        Route::post('settings/restore', [SettingsController::class, 'restore'])->name('admin.settings.restore');
        Route::post('settings/reset-defaults', [SettingsController::class, 'resetToDefaults'])->name('admin.settings.reset-defaults');
    });

    // Monitoring Dashboard (Monitor role and above)
    Route::middleware('can:view-monitoring')->group(function () {
        Route::get('monitoring', [MonitoringController::class, 'index'])->name('admin.monitoring.index');
        Route::get('monitoring/live-tracking', [MonitoringController::class, 'liveTracking'])->name('admin.monitoring.live-tracking');
        Route::get('monitoring/device-trust', [MonitoringController::class, 'deviceTrust'])->name('admin.monitoring.device-trust');
        Route::post('monitoring/device-trust/{token}/adjust', [MonitoringController::class, 'adjustTrustScore'])->name('admin.monitoring.adjust-trust');
        Route::get('monitoring/analytics', [MonitoringController::class, 'analytics'])->name('admin.monitoring.analytics');
        Route::get('monitoring/real-time-data', [MonitoringController::class, 'getRealTimeData'])->name('admin.monitoring.real-time-data');
    });
});