<?php
require_once __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "DB_CONNECTION from env(): " . env('DB_CONNECTION') . "\n";
echo "DB_CONNECTION from getenv(): " . getenv('DB_CONNECTION') . "\n";
echo "DB_CONNECTION from \$_ENV: " . ($_ENV['DB_CONNECTION'] ?? 'NULL') . "\n";
echo "Default in bootstrap: pgsql\n";

$connection = Capsule::connection();
echo "Actual Driver: " . $connection->getDriverName() . "\n";
echo "Database Name: " . $connection->getDatabaseName() . "\n";

try {
    $pdo = $connection->getPdo();
    echo "PDO Driver Name: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    
    // Create temp table
    $pdo->exec("CREATE TEMPORARY TABLE test_returning (id SERIAL PRIMARY KEY, val TEXT)");
    echo "Temp table created.\n";
    
    // List goals
    $stmt = $pdo->prepare("SELECT * FROM goals");
    $stmt->execute();
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Goals:\n";
    print_r($goals);

    // List transactions
    $stmt = $pdo->prepare("SELECT * FROM transactions");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Transactions:\n";
    print_r($transactions);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
