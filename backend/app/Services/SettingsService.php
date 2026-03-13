<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("settings.{$key}", 3600, function() use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, string $type = 'text', string $group = 'general'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
            ]
        );

        $this->clearCache();
    }

    /**
     * Get all settings in a group.
     */
    public function getGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function() use ($group) {
            $settings = Setting::group($group)->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;
        });
    }

    /**
     * Update multiple settings at once.
     */
    public function updateBatch(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if ($key === '_token') {
                continue;
            }

            // Determine type based on value
            $type = 'text';
            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_numeric($value)) {
                $type = 'number';
            } elseif (is_array($value)) {
                $type = 'json';
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                ]
            );
        }

        $this->clearCache();
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): void
    {
        // Clear all settings caches
        $settings = Setting::all();

        foreach ($settings as $setting) {
            Cache::forget("settings.{$setting->key}");
        }

        // Clear group caches
        Cache::forget('settings.group.general');
        Cache::forget('settings.group.email');
    }
}
