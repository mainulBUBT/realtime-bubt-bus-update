<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Creating sample bus positions...\n";

// Sample bus positions for Dhaka
$busPositions = [
    'B1' => ['lat' => 23.7937, 'lng' => 90.3629, 'name' => 'Buriganga'],
    'B2' => ['lat' => 23.7850, 'lng' => 90.3700, 'name' => 'Brahmaputra'],
    'B3' => ['lat' => 23.7693, 'lng' => 90.3563, 'name' => 'Padma'],
    'B4' => ['lat' => 23.7550, 'lng' => 90.3850, 'name' => 'Meghna'],
    'B5' => ['lat' => 23.8213, 'lng' => 90.3541, 'name' => 'Jamuna'],
];

foreach ($busPositions as $busId => $position) {
    \App\Models\BusCurrentPosition::updateOrCreate(
        ['bus_id' => $busId],
        [
            'latitude' => $position['lat'],
            'longitude' => $position['lng'],
            'status' => 'active',
            'active_trackers' => rand(1, 5),
            'confidence_level' => rand(70, 95) / 100,
            'average_trust_score' => rand(60, 90) / 100,
            'movement_consistency' => rand(70, 95) / 100,
            'last_updated' => now(),
            'speed' => rand(0, 40),
        ]
    );
    
    echo "Created position for Bus {$busId} ({$position['name']}) at {$position['lat']}, {$position['lng']}\n";
}

echo "Sample bus positions created successfully!\n";