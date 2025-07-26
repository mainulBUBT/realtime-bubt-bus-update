<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BusinessSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description'
    ];

    protected $casts = [
        'value' => 'json'
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("business_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value, string $type = 'string', string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description
            ]
        );

        // Clear cache
        Cache::forget("business_setting_{$key}");
    }

    /**
     * Get multiple settings by keys
     */
    public static function getMultiple(array $keys): array
    {
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = static::get($key);
        }
        return $settings;
    }

    /**
     * Get all PWA related settings
     */
    public static function getPwaSettings(): array
    {
        return static::getMultiple([
            'pwa_app_name',
            'pwa_app_description',
            'pwa_theme_color',
            'pwa_background_color',
            'pwa_logo_url',
            'pwa_header_text'
        ]);
    }

    /**
     * Get all university information settings
     */
    public static function getUniversitySettings(): array
    {
        return static::getMultiple([
            'university_name',
            'university_address',
            'university_contact_phone',
            'university_contact_email',
            'university_website'
        ]);
    }

    /**
     * Get all system configuration settings
     */
    public static function getSystemSettings(): array
    {
        return static::getMultiple([
            'tracking_interval_seconds',
            'location_validation_radius',
            'trust_score_threshold',
            'max_speed_kmh',
            'data_retention_days',
            'websocket_enabled'
        ]);
    }

    /**
     * Initialize default settings
     */
    public static function initializeDefaults(): void
    {
        $defaults = [
            // PWA Settings
            'pwa_app_name' => ['value' => 'Bus Tracker', 'type' => 'string', 'description' => 'PWA application name'],
            'pwa_app_description' => ['value' => 'University Bus Tracking System', 'type' => 'string', 'description' => 'PWA application description'],
            'pwa_theme_color' => ['value' => '#007bff', 'type' => 'string', 'description' => 'PWA theme color'],
            'pwa_background_color' => ['value' => '#ffffff', 'type' => 'string', 'description' => 'PWA background color'],
            'pwa_header_text' => ['value' => 'Bus Tracker', 'type' => 'string', 'description' => 'Header text displayed in app'],

            // University Settings
            'university_name' => ['value' => 'University Name', 'type' => 'string', 'description' => 'Name of the university'],
            'university_address' => ['value' => 'University Address', 'type' => 'string', 'description' => 'University address'],
            'university_contact_phone' => ['value' => '+880-XXX-XXXXXXX', 'type' => 'string', 'description' => 'University contact phone'],
            'university_contact_email' => ['value' => 'contact@university.edu', 'type' => 'string', 'description' => 'University contact email'],

            // System Settings
            'tracking_interval_seconds' => ['value' => 30, 'type' => 'integer', 'description' => 'GPS tracking interval in seconds'],
            'location_validation_radius' => ['value' => 500, 'type' => 'integer', 'description' => 'Location validation radius in meters'],
            'trust_score_threshold' => ['value' => 0.7, 'type' => 'float', 'description' => 'Minimum trust score for trusted users'],
            'max_speed_kmh' => ['value' => 80, 'type' => 'integer', 'description' => 'Maximum allowed speed in km/h'],
            'data_retention_days' => ['value' => 30, 'type' => 'integer', 'description' => 'Data retention period in days'],
            'websocket_enabled' => ['value' => true, 'type' => 'boolean', 'description' => 'Enable WebSocket real-time updates']
        ];

        foreach ($defaults as $key => $config) {
            if (!static::where('key', $key)->exists()) {
                static::create([
                    'key' => $key,
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description']
                ]);
            }
        }
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("business_setting_{$key}");
        }
    }

    /**
     * Boot method to clear cache when settings are updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("business_setting_{$setting->key}");
        });

        static::deleted(function ($setting) {
            Cache::forget("business_setting_{$setting->key}");
        });
    }
}
