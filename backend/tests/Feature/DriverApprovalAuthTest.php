<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DriverApprovalAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_registration_creates_pending_account_without_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Pending Driver',
            'email' => 'driver@example.com',
            'phone' => '01700000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'driver',
        ]);

        $response->assertCreated();
        $response->assertJsonFragment([
            'message' => 'Your account is waiting for admin approval.',
            'requires_approval' => true,
        ]);
        $response->assertJsonMissingPath('token');

        $this->assertDatabaseHas('users', [
            'email' => 'driver@example.com',
            'role' => 'driver',
            'approval_status' => 'pending',
        ]);
    }

    public function test_pending_driver_cannot_log_in(): void
    {
        User::factory()->create([
            'name' => 'Pending Driver',
            'email' => 'driver@example.com',
            'password' => Hash::make('password123'),
            'role' => 'driver',
            'approval_status' => 'pending',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'driver@example.com',
            'password' => 'password123',
            'role' => 'driver',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'Your account is waiting for admin approval.',
        ]);
    }

    public function test_student_registration_still_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Student User',
            'email' => 'student@example.com',
            'phone' => '01800000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.role', 'student');
        $response->assertJsonPath('user.approval_status', 'approved');
        $this->assertNotEmpty($response->json('token'));
    }
}
