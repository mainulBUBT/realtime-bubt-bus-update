#!/usr/bin/env php
<?php

declare(strict_types=1);

$appType = $argv[1] ?? null;

if (!in_array($appType, ['student', 'driver'], true)) {
    fwrite(STDERR, "Usage: resolve-app-branding.php <student|driver>\n");
    exit(1);
}

$defaults = [
    'student' => [
        'APP_NAME' => 'BUBT Tracker',
        'BRAND_COLOR' => '#4F46E5',
        'BRAND_COLOR_DARK' => '#4338CA',
    ],
    'driver' => [
        'APP_NAME' => 'BUBT Driver',
        'BRAND_COLOR' => '#059669',
        'BRAND_COLOR_DARK' => '#047857',
    ],
];

$values = $defaults[$appType];
$rootDir = dirname(__DIR__);
$backendDir = dirname($rootDir) . '/backend';

if (is_dir($backendDir . '/vendor') && file_exists($backendDir . '/bootstrap/app.php')) {
    try {
        require $backendDir . '/vendor/autoload.php';

        $app = require $backendDir . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $settings = app(App\Services\SettingsService::class);
        $nameKey = "{$appType}_app_name";
        $primaryKey = "{$appType}_splash_primary_color";
        $secondaryKey = "{$appType}_splash_secondary_color";

        $resolvedName = $settings->get($nameKey, $values['APP_NAME']);
        $resolvedPrimary = $settings->get($primaryKey, $values['BRAND_COLOR']);
        $resolvedSecondary = $settings->get($secondaryKey, $values['BRAND_COLOR_DARK']);

        if (is_string($resolvedName) && $resolvedName !== '') {
            $values['APP_NAME'] = $resolvedName;
        }

        if (is_string($resolvedPrimary) && preg_match('/^#[0-9A-Fa-f]{6}$/', $resolvedPrimary)) {
            $values['BRAND_COLOR'] = strtoupper($resolvedPrimary);
        }

        if (is_string($resolvedSecondary) && preg_match('/^#[0-9A-Fa-f]{6}$/', $resolvedSecondary)) {
            $values['BRAND_COLOR_DARK'] = strtoupper($resolvedSecondary);
        }
    } catch (Throwable $e) {
        // Fall back to defaults if Laravel/bootstrap or DB access is unavailable.
    }
}

foreach ($values as $key => $value) {
    $escaped = str_replace("'", "'\\''", $value);
    echo $key . "='" . $escaped . "'\n";
}
