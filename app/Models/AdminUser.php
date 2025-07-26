<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Check if admin has super admin role
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if admin has admin role or higher
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    /**
     * Check if admin has monitor role or higher
     */
    public function isMonitor(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'monitor']);
    }

    /**
     * Check if admin can manage buses
     */
    public function canManageBuses(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if admin can manage schedules
     */
    public function canManageSchedules(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if admin can manage settings
     */
    public function canManageSettings(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if admin can view monitoring dashboard
     */
    public function canViewMonitoring(): bool
    {
        return $this->isMonitor();
    }

    /**
     * Scope to get active admin users
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get admin users by role
     */
    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayAttribute(): string
    {
        return match($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Administrator',
            'monitor' => 'Monitor',
            default => 'Unknown'
        };
    }
}
