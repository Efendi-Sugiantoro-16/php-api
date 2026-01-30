<?php
// temp_create_db.php
$host = 'localhost';
$port = '5432';
$user = 'postgres';
$pass = ''; // Assuming empty based on .env

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;user=$user;password=$pass");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE goalmoney_db");
    echo "Database 'goalmoney_db' created successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
