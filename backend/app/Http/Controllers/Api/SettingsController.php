<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settings
    ) {}

    /**
     * Get public app settings (no auth required)
     * Returns settings for the specified app type
     */
    public function getAppSettings(Request $request)
    {
        $appType = $request->query('app_type', 'student');

        // Validate app type
        if (!in_array($appType, ['student', 'driver'])) {
            return response()->json(['error' => 'Invalid app_type'], 400);
        }

        $group = $appType . '_app';
        $settings = $this->settings->getGroup($group);

        // getGroup() already returns the correct format: ['key' => 'value', ...]
        // No transformation needed - return directly
        return response()->json($settings);
    }
}
