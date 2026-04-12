<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Driver\TripController;
use App\Http\Controllers\Api\Driver\LocationController;
use App\Http\Controllers\Api\Driver\ResourceController;
use App\Http\Controllers\Api\Student\TrackingController;
use App\Http\Controllers\Api\Admin\BusController;
use App\Http\Controllers\Api\Admin\RouteController;
use App\Http\Controllers\Api\Admin\ScheduleController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\Student\ProfileController;
use App\Http\Controllers\Api\Student\NotificationController as StudentNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::get('/settings', [SettingsController::class, 'getAppSettings']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::patch('/auth/password', [AuthController::class, 'updatePassword']);

    // Driver routes
    Route::middleware('role:driver')->prefix('driver')->group(function () {
        // Available resources for starting a trip
        Route::get('/buses', [ResourceController::class, 'buses']);
        Route::get('/routes', [ResourceController::class, 'routes']);

        // Trip management
        Route::post('/trips/start', [TripController::class, 'start']);
        Route::post('/trips/{trip}/end', [TripController::class, 'end']);
        Route::get('/trips/current', [TripController::class, 'current']);
        Route::get('/trips/history', [TripController::class, 'history']);
        Route::post('/location', [LocationController::class, 'update']);
        Route::post('/location/batch', [LocationController::class, 'batchUpdate']);
    });

    // Student routes
    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/routes', [TrackingController::class, 'routes']);
        Route::get('/routes/{id}', [TrackingController::class, 'routeDetail']);
        Route::get('/trips/active', [TrackingController::class, 'activeTrips']);
        Route::get('/trips/{tripId}/locations', [TrackingController::class, 'tripLocations']);
        Route::get('/trips/{tripId}/latest-location', [TrackingController::class, 'latestLocation']);
        Route::get('/schedules', [TrackingController::class, 'schedules']);

        // FCM token
        Route::post('/fcm-token', [ProfileController::class, 'updateFcmToken']);

        // Notifications
        Route::get('/notifications', [StudentNotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [StudentNotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [StudentNotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [StudentNotificationController::class, 'markAllRead']);
    });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('buses', BusController::class);
        Route::apiResource('routes', RouteController::class);
        Route::apiResource('schedules', ScheduleController::class);
    });
});
