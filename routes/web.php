<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\TodayTrips;
use App\Livewire\AdminDashboard;

// Authentication routes
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('auth');
});

Route::get('/dashboard', function () {
    if (!auth()->check()) {
        return redirect('/');
    }
    return view('dashboard');
})->name('dashboard');

Route::get('/admin', function () {
    return view('admin');
})->name('admin');

Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
})->name('logout');

// API Routes for mobile apps and external services
Route::prefix('api')->group(function () {
    Route::post('/ping', [App\Http\Controllers\Api\LocationController::class, 'ping'])
        ->middleware('throttle:4,1'); // 4 requests per minute
    
    Route::get('/positions', [App\Http\Controllers\Api\LocationController::class, 'positions']);
    
    Route::post('/push-subscribe', function (Illuminate\Http\Request $request) {
        // Handle push notification subscription
        $subscription = App\Models\PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'public_key' => $request->keys['p256dh'] ?? '',
                'auth_token' => $request->keys['auth'] ?? '',
                'subscribed_stops' => $request->subscribed_stops ?? []
            ]
        );
        
        return response()->json(['success' => true]);
    });
});
