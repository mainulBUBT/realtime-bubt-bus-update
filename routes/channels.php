<?php

use Illuminate\Support\Facades\Broadcast;
use App\Services\BusScheduleService;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Bus Tracking Channels
|--------------------------------------------------------------------------
*/

// Public channel for all bus updates
Broadcast::channel('bus.all', function () {
    return true; // Public channel, anyone can listen
});

// Public channel for specific bus updates
Broadcast::channel('bus.{busId}', function ($user, $busId) {
    // Validate that the bus exists and is active
    $scheduleService = app(BusScheduleService::class);
    $activeStatus = $scheduleService->isBusActive($busId);
    
    return $activeStatus['is_active'];
});

// Presence channel for tracking active users on a bus
Broadcast::channel('bus.{busId}.tracking', function ($user, $busId) {
    // Allow anonymous users to join tracking channels
    // Return user info for presence channel
    return [
        'id' => $user->id ?? 'anonymous_' . uniqid(),
        'name' => $user->name ?? 'Anonymous User',
        'joined_at' => now()->toISOString()
    ];
});

// Admin-only channel for monitoring
Broadcast::channel('admin.monitoring', function ($user) {
    // Only allow admin users
    return $user && isset($user->is_admin) && $user->is_admin;
});

// Connection status channel
Broadcast::channel('tracking.status', function () {
    return true; // Public channel for connection status updates
});
