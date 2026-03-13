<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\SettingsService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share application name with all views
        View::composer('*', function ($view) {
            $settingsService = app(SettingsService::class);
            $view->with('appName', $settingsService->get('app_name', 'Bus Tracker'));
        });
    }
}
