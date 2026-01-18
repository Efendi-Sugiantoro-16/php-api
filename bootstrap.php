<?php
// bootstrap.php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables (optional - for local development)
// On production (Railway), environment variables are set directly
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createMutable(__DIR__);
    $dotenv->load();
}

// Setup Eloquent ORM
$capsule = new Capsule;

$capsule->addConnection([
    'driver' => env('DB_CONNECTION', 'pgsql'),
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'goalmoney_db'),
    'username' => env('DB_USERNAME', 'postgres'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'schema' => 'public',
]);

// Make this Capsule instance available globally
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

// Helper function untuk mengakses env
function env($key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Convert string boolean to actual boolean
    if (in_array(strtolower($value), ['true', '(true)'])) {
        return true;
    }
    
    if (in_array(strtolower($value), ['false', '(false)'])) {
        return false;
    }
    
    return $value;
}

// Set timezone
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
?>