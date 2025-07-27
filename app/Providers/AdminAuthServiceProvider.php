<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\AdminUser;

class AdminAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define admin gates
        Gate::define('manage-buses', function (AdminUser $admin) {
            return $admin->canManageBuses();
        });

        Gate::define('manage-schedules', function (AdminUser $admin) {
            return $admin->canManageSchedules();
        });

        Gate::define('manage-settings', function (AdminUser $admin) {
            return $admin->canManageSettings();
        });

        Gate::define('view-monitoring', function (AdminUser $admin) {
            return $admin->canViewMonitoring();
        });
    }
}