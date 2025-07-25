<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceTokenController;

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

Route::middleware('api')->group(function () {
    // Device Token Management Routes
    Route::prefix('device-token')->group(function () {
        Route::post('/register', [DeviceTokenController::class, 'register']);
        Route::post('/validate', [DeviceTokenController::class, 'validate']);
        Route::get('/stats', [DeviceTokenController::class, 'getStats']);
    });
});