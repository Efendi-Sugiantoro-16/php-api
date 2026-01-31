<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

$pdo = Capsule::connection()->getPdo();

echo "=== Badges Table Structure ===\n";
$cols = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'badges' AND table_schema = 'public' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_OBJ);
foreach($cols as $col) {
    echo "- {$col->column_name} ({$col->data_type})\n";
}

echo "\n=== User Badges Table Structure ===\n";
$cols2 = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'user_badges' AND table_schema = 'public' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_OBJ);
foreach($cols2 as $col) {
    echo "- {$col->column_name} ({$col->data_type})\n";
}
?>
