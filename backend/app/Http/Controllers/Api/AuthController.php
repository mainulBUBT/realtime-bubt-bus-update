<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Trip;
use App\Events\BusTripEnded;

class AuthController extends Controller
{
    /**
     * Login for both drivers and students
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:admin,driver,student',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Debug logging
        \Log::info('Login attempt', [
            'email' => $request->email,
            'request_role' => $request->role,
            'request_role_type' => gettype($request->role),
            'user_role' => $user->role,
            'user_role_type' => gettype($user->role),
            'match' => $user->role === $request->role,
        ]);

        if ($user->role !== $request->role) {
            return response()->json([
                'message' => 'Unauthorized for this role',
                'debug' => [
                    'expected' => $user->role,
                    'received' => $request->role,
                ]
            ], 403);
        }

        // Revoke old tokens
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // If the driver is logging out with an ongoing trip, end it cleanly
        if ($user->role === 'driver') {
            $activeTrip = Trip::where('driver_id', $user->id)
                ->activeToday()
                ->first();

            if ($activeTrip) {
                $activeTrip->update(['status' => 'completed', 'ended_at' => now()]);

                broadcast(new BusTripEnded($activeTrip->bus_id, $activeTrip->id));
            }
        }

        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update authenticated user's profile (name + phone only)
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');
        $user->save();

        return response()->json($user);
    }

    /**
     * Update authenticated user's password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->password = $request->input('password');
        $user->save();

        return response()->json(['message' => 'Password updated']);
    }
}
