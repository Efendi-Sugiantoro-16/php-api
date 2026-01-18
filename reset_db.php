<?php
require_once __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Dropping tables...\n";

try {
    Capsule::schema()->dropIfExists('transactions');
    echo "✓ Dropped transactions\n";
    
    Capsule::schema()->dropIfExists('tokens');
    echo "✓ Dropped tokens\n";
    
    Capsule::schema()->dropIfExists('goals');
    echo "✓ Dropped goals\n";
    
    Capsule::schema()->dropIfExists('users');
    echo "✓ Dropped users\n";
    
    echo "✅ Tables dropped successfully.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
