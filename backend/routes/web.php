<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Login Page
Route::get('/', [PageController::class, 'login'])->name('login');

// Logout
Route::post('/logout', function() {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// Main App (Protected - Session Auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/app', [PageController::class, 'app'])->name('app');
    Route::post('/profile/update', [PageController::class, 'updateProfile'])->name('profile.update');
});

// Google Auth Routes
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Admin Auth Routes
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

// Admin Routes (Protected)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('buses', \App\Http\Controllers\Admin\BusController::class);
    Route::resource('routes', \App\Http\Controllers\Admin\RouteController::class);
    Route::resource('schedules', \App\Http\Controllers\Admin\ScheduleController::class);
    Route::resource('schedule-periods', \App\Http\Controllers\Admin\SchedulePeriodController::class);
    Route::resource('trips', \App\Http\Controllers\Admin\TripController::class);
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);

    // Settings routes with tab support
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('settings.update.general');
        Route::post('/email', [SettingsController::class, 'updateEmail'])->name('settings.update.email');
        Route::get('/database/info', [SettingsController::class, 'getDatabaseInfo'])->name('settings.database.info');
        Route::post('/database/truncate', [SettingsController::class, 'truncateTable'])->name('settings.database.truncate');
        Route::post('/mobile/{type}', [SettingsController::class, 'updateMobile'])->name('settings.update.mobile');
    });

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/students', [\App\Http\Controllers\Admin\NotificationController::class, 'students'])->name('notifications.students');
    Route::post('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'store'])->name('notifications.send');
    Route::get('/notifications/{campaign}/edit', [\App\Http\Controllers\Admin\NotificationController::class, 'edit'])->name('notifications.edit');
    Route::put('/notifications/{campaign}', [\App\Http\Controllers\Admin\NotificationController::class, 'update'])->name('notifications.update');
    Route::post('/notifications/{campaign}/resend', [\App\Http\Controllers\Admin\NotificationController::class, 'resend'])->name('notifications.resend');
});
