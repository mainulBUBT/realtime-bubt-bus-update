<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    /**
     * Display business settings
     */
    public function index(): View
    {
        $settings = [
            // PWA Settings
            'app_name' => BusinessSetting::get('app_name', 'Bus Tracker'),
            'app_description' => BusinessSetting::get('app_description', 'University Bus Tracking System'),
            'app_logo' => BusinessSetting::get('app_logo'),
            'app_primary_color' => BusinessSetting::get('app_primary_color', '#007bff'),
            'app_secondary_color' => BusinessSetting::get('app_secondary_color', '#6c757d'),
            'header_text' => BusinessSetting::get('header_text', 'Bus Tracker'),
            
            // University Information
            'university_name' => BusinessSetting::get('university_name', 'University Name'),
            'university_address' => BusinessSetting::get('university_address'),
            'university_phone' => BusinessSetting::get('university_phone'),
            'university_email' => BusinessSetting::get('university_email'),
            'university_website' => BusinessSetting::get('university_website'),
            
            // Tracking Settings
            'tracking_interval' => BusinessSetting::get('tracking_interval', 30),
            'location_accuracy_threshold' => BusinessSetting::get('location_accuracy_threshold', 50),
            'trust_score_threshold' => BusinessSetting::get('trust_score_threshold', 0.7),
            'max_speed_threshold' => BusinessSetting::get('max_speed_threshold', 80),
            'route_radius_tolerance' => BusinessSetting::get('route_radius_tolerance', 200),
            
            // Notification Settings
            'enable_notifications' => BusinessSetting::get('enable_notifications', true),
            'notification_title_template' => BusinessSetting::get('notification_title_template', 'Bus {bus_id} Update'),
            'notification_body_template' => BusinessSetting::get('notification_body_template', 'Bus {bus_id} is now at {location}'),
            'notification_delay_threshold' => BusinessSetting::get('notification_delay_threshold', 10),
            
            // System Settings
            'data_retention_days' => BusinessSetting::get('data_retention_days', 30),
            'max_concurrent_trackers' => BusinessSetting::get('max_concurrent_trackers', 300),
            'enable_debug_mode' => BusinessSetting::get('enable_debug_mode', false),
            'maintenance_mode' => BusinessSetting::get('maintenance_mode', false),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update business settings
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // PWA Settings
            'app_name' => 'required|string|max:100',
            'app_description' => 'required|string|max:255',
            'app_primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'app_secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'header_text' => 'required|string|max:50',
            
            // University Information
            'university_name' => 'required|string|max:100',
            'university_address' => 'nullable|string|max:255',
            'university_phone' => 'nullable|string|max:20',
            'university_email' => 'nullable|email|max:100',
            'university_website' => 'nullable|url|max:255',
            
            // Tracking Settings
            'tracking_interval' => 'required|integer|min:10|max:300',
            'location_accuracy_threshold' => 'required|integer|min:10|max:200',
            'trust_score_threshold' => 'required|numeric|min:0.1|max:1.0',
            'max_speed_threshold' => 'required|integer|min:20|max:150',
            'route_radius_tolerance' => 'required|integer|min:50|max:1000',
            
            // Notification Settings
            'enable_notifications' => 'boolean',
            'notification_title_template' => 'required|string|max:100',
            'notification_body_template' => 'required|string|max:255',
            'notification_delay_threshold' => 'required|integer|min:1|max:60',
            
            // System Settings
            'data_retention_days' => 'required|integer|min:7|max:365',
            'max_concurrent_trackers' => 'required|integer|min:50|max:1000',
            'enable_debug_mode' => 'boolean',
            'maintenance_mode' => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                BusinessSetting::set($key, $value, $this->getSettingType($key));
            }
        });

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            $oldLogo = BusinessSetting::get('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            // Store new logo
            $logoPath = $request->file('logo')->store('logos', 'public');
            BusinessSetting::set('app_logo', $logoPath, 'string');

            return redirect()->route('admin.settings.index')
                ->with('success', 'Logo uploaded successfully.');
        }

        return redirect()->route('admin.settings.index')
            ->with('error', 'Failed to upload logo.');
    }

    /**
     * Create settings backup
     */
    public function backup(): RedirectResponse
    {
        try {
            $settings = BusinessSetting::all()->pluck('value', 'key')->toArray();
            $backup = [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
                'settings' => $settings
            ];

            $filename = 'settings_backup_' . now()->format('Y_m_d_H_i_s') . '.json';
            $path = 'backups/' . $filename;
            
            Storage::disk('local')->put($path, json_encode($backup, JSON_PRETTY_PRINT));

            return redirect()->route('admin.settings.index')
                ->with('success', "Settings backup created: {$filename}");
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    /**
     * Restore settings from backup
     */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:json|max:1024'
        ]);

        try {
            $content = file_get_contents($request->file('backup_file')->getRealPath());
            $backup = json_decode($content, true);

            if (!isset($backup['settings']) || !is_array($backup['settings'])) {
                throw new \Exception('Invalid backup file format');
            }

            DB::transaction(function () use ($backup) {
                foreach ($backup['settings'] as $key => $value) {
                    BusinessSetting::set($key, $value, $this->getSettingType($key));
                }
            });

            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings restored successfully from backup.');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Failed to restore backup: ' . $e->getMessage());
        }
    }

    /**
     * Get setting type based on key
     */
    private function getSettingType(string $key): string
    {
        $booleanSettings = [
            'enable_notifications',
            'enable_debug_mode',
            'maintenance_mode'
        ];

        $integerSettings = [
            'tracking_interval',
            'location_accuracy_threshold',
            'max_speed_threshold',
            'route_radius_tolerance',
            'notification_delay_threshold',
            'data_retention_days',
            'max_concurrent_trackers'
        ];

        $floatSettings = [
            'trust_score_threshold'
        ];

        if (in_array($key, $booleanSettings)) {
            return 'boolean';
        } elseif (in_array($key, $integerSettings)) {
            return 'integer';
        } elseif (in_array($key, $floatSettings)) {
            return 'float';
        }

        return 'string';
    }

    /**
     * Reset settings to defaults
     */
    public function resetToDefaults(): RedirectResponse
    {
        try {
            DB::transaction(function () {
                BusinessSetting::truncate();
                
                // Set default values
                $defaults = [
                    'app_name' => 'Bus Tracker',
                    'app_description' => 'University Bus Tracking System',
                    'app_primary_color' => '#007bff',
                    'app_secondary_color' => '#6c757d',
                    'header_text' => 'Bus Tracker',
                    'university_name' => 'University Name',
                    'tracking_interval' => 30,
                    'location_accuracy_threshold' => 50,
                    'trust_score_threshold' => 0.7,
                    'max_speed_threshold' => 80,
                    'route_radius_tolerance' => 200,
                    'enable_notifications' => true,
                    'notification_title_template' => 'Bus {bus_id} Update',
                    'notification_body_template' => 'Bus {bus_id} is now at {location}',
                    'notification_delay_threshold' => 10,
                    'data_retention_days' => 30,
                    'max_concurrent_trackers' => 300,
                    'enable_debug_mode' => false,
                    'maintenance_mode' => false,
                ];

                foreach ($defaults as $key => $value) {
                    BusinessSetting::set($key, $value, $this->getSettingType($key));
                }
            });

            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings reset to defaults successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Failed to reset settings: ' . $e->getMessage());
        }
    }
}