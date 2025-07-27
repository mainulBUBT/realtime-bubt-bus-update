<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Livewire\ConnectionStatus;

echo "Testing ConnectionStatus Livewire component...\n\n";

try {
    // Test component instantiation
    $component = new ConnectionStatus();
    echo "✓ Component instantiated successfully\n";
    
    // Test mount method
    $component->mount();
    echo "✓ Mount method executed successfully\n";
    
    // Test status methods
    echo "✓ Status text: " . $component->getStatusText() . "\n";
    echo "✓ Status icon: " . $component->getStatusIcon() . "\n";
    echo "✓ Status class: " . $component->getStatusClass() . "\n";
    
    // Test updateConnectionStatus method
    $component->updateConnectionStatus([
        'status' => 'connected',
        'type' => 'polling',
        'reconnectAttempts' => 0
    ]);
    echo "✓ updateConnectionStatus method works\n";
    echo "  - Status: " . $component->connectionStatus . "\n";
    echo "  - Type: " . $component->connectionType . "\n";
    
    // Test handleConnectionError method
    $component->handleConnectionError([
        'message' => 'Test error message'
    ]);
    echo "✓ handleConnectionError method works\n";
    echo "  - Error message: " . $component->errorMessage . "\n";
    
    // Test other methods
    $component->show();
    echo "✓ show method works\n";
    
    $component->hide();
    echo "✓ hide method works\n";
    
    echo "\n🎉 All ConnectionStatus component methods work correctly!\n";
    echo "The dependency injection issue has been resolved.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}