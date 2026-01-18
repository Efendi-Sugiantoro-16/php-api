# GoalMoney Backend API - Complete Code Documentation

## 📋 Table of Contents

1. [Database Schema](#database-schema)
2. [Composer Configuration](#composer-configuration)
3. [Environment Configuration](#environment-configuration)
4. [Bootstrap & Core Setup](#bootstrap--core-setup)
5. [Models](#models)
6. [Helpers](#helpers)
7. [Middleware](#middleware)
8. [Config](#config)
9. [API Endpoints - Authentication](#api-endpoints---authentication)
10. [API Endpoints - Goals](#api-endpoints---goals)
11. [API Endpoints - Transactions](#api-endpoints---transactions)
12. [API Endpoints - Profile & Dashboard](#api-endpoints---profile--dashboard)
13. [Utilities](#utilities)
14. [Migration & Seeder Scripts](#migration--seeder-scripts)

---

## Database Schema

### File: `database_schema.sql`

```sql
-- Database Schema untuk GoalMoney
-- PostgreSQL

-- Tabel Users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Goals (Tujuan Tabungan)
CREATE TABLE goals (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0,
    deadline DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Transactions (Setoran Tabungan)
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    goal_id INTEGER NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
);

-- Tabel Tokens (untuk autentikasi)
CREATE TABLE tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index untuk performa
CREATE INDEX idx_goals_user_id ON goals(user_id);
CREATE INDEX idx_transactions_goal_id ON transactions(goal_id);
CREATE INDEX idx_tokens_token ON tokens(token);
CREATE INDEX idx_tokens_user_id ON tokens(user_id);

-- Tabel Withdrawals (Penarikan)
CREATE TABLE withdrawals (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    method VARCHAR(50),          -- 'dana', 'gopay', 'bank_transfer', 'ovo', 'shopeepay'
    account_number VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending',  -- pending, approved, rejected
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_withdrawals_user_id ON withdrawals(user_id);
CREATE INDEX idx_withdrawals_status ON withdrawals(status);
```

---

## Composer Configuration

### File: `composer.json`

```json
{
    "name": "goalmoney/api",
    "description": "GoalMoney REST API with Eloquent ORM",
    "type": "project",
    "require": {
        "php": "^7.4|^8.0",
        "illuminate/database": "^9.0|^10.0",
        "vlucas/phpdotenv": "^5.5"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "App\\Models\\": "app/models/",
            "App\\Middleware\\": "app/middleware/",
            "App\\Helpers\\": "app/helpers/"
        }
    }
}
```

---

## Environment Configuration

### File: `.env.example`

```bash
# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=goalmoney_db
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Application
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta

# Token
TOKEN_EXPIRY_DAYS=30
```

### File: `.env`

```bash
# Copy dari .env.example dan sesuaikan dengan konfigurasi Anda
# JANGAN commit file ini ke git!

DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=goalmoney_db
DB_USERNAME=postgres
DB_PASSWORD=your_password

APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta

TOKEN_EXPIRY_DAYS=30
```

---

## Bootstrap & Core Setup

### File: `bootstrap.php`

```php
<?php
// bootstrap.php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
```

### File: `index.php`

```php
<?php
// index.php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/cors.php';

http_response_code(200);

$response = [
    'success' => true,
    'message' => 'GoalMoney API v1.0',
    'endpoints' => [
        'auth' => [
            'POST /api/auth/register' => 'Register new user',
            'POST /api/auth/login' => 'Login user'
        ],
        'profile' => [
            'GET /api/profile/user' => 'Get user profile (requires auth)'
        ],
        'dashboard' => [
            'GET /api/dashboard/summary' => 'Get dashboard summary (requires auth)'
        ],
        'goals' => [
            'GET /api/goals/index' => 'Get all goals (requires auth)',
            'POST /api/goals/store' => 'Create new goal (requires auth)',
            'PUT /api/goals/update' => 'Update goal (requires auth)',
            'DELETE /api/goals/delete' => 'Delete goal (requires auth)'
        ],
            'transactions' => [
                'GET /api/transactions/index?goal_id={id}' => 'Get transactions by goal (requires auth)',
                'POST /api/transactions/store' => 'Create new transaction (requires auth)',
                'DELETE /api/transactions/delete' => 'Delete transaction (requires auth)'
            ],
            'withdrawals' => [
                'POST /api/withdrawals/request' => 'Request withdrawal (requires auth)',
                'GET /api/withdrawals/index' => 'Get withdrawal history (requires auth)',
                'POST /api/withdrawals/approve' => 'Approve/reject withdrawal (admin)'
            ]
    ],
    'authentication' => [
        'type' => 'Bearer Token',
        'header' => 'Authorization: Bearer {token}'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
```

---

## Models

### File: `app/models/User.php`

```php
<?php
// app/models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    protected $table = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'password'
    ];
    
    protected $hidden = [
        'password'
    ];
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    // Relationships
    public function goals() {
        return $this->hasMany(Goal::class);
    }
    
    public function tokens() {
        return $this->hasMany(Token::class);
    }
    
    // Hash password automatically
    public function setPasswordAttribute($value) {
        $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT);
    }
    
    // Verify password
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
}
?>
```

### File: `app/models/Goal.php`

```php
<?php
// app/models/Goal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model {
    protected $table = 'goals';
    
    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'deadline',
        'description'
    ];
    
    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline' => 'date'
    ];
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    // Relationships
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function transactions() {
        return $this->hasMany(Transaction::class);
    }
    
    // Accessor untuk progress percentage
    public function getProgressPercentageAttribute() {
        if ($this->target_amount > 0) {
            return round(($this->current_amount / $this->target_amount) * 100, 2);
        }
        return 0;
    }
    
    // Method untuk menambah saldo
    public function addAmount($amount) {
        $this->current_amount += $amount;
        $this->save();
    }
    
    // Method untuk mengurangi saldo
    public function subtractAmount($amount) {
        $this->current_amount -= $amount;
        $this->save();
    }
}
?>
```

### File: `app/models/Transaction.php`

```php
<?php
// app/models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {
    protected $table = 'transactions';
    
    protected $fillable = [
        'goal_id',
        'amount',
        'method',
        'description',
        'transaction_date'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime'
    ];
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // No updated_at for transactions
    
    // Relationships
    public function goal() {
        return $this->belongsTo(Goal::class);
    }
    
    // Boot method untuk auto update goal amount
    protected static function boot() {
        parent::boot();
        
        // Ketika transaction dibuat, tambah amount ke goal
        static::created(function ($transaction) {
            $goal = $transaction->goal;
            if ($goal) {
                $goal->addAmount($transaction->amount);
            }
        });
        
        // Ketika transaction dihapus, kurangi amount dari goal
        static::deleted(function ($transaction) {
            $goal = $transaction->goal;
            if ($goal) {
                $goal->subtractAmount($transaction->amount);
            }
        });
    }
}
?>
```

### File: `app/models/Token.php`

```php
<?php
// app/models/Token.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model {
    protected $table = 'tokens';
    
    protected $fillable = [
        'user_id',
        'token',
        'expires_at'
    ];
    
    protected $casts = [
        'expires_at' => 'datetime'
    ];
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    // Relationships
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    // Generate new token
    public static function generateToken($userId) {
        // Delete existing tokens for this user
        static::where('user_id', $userId)->delete();
        
        // Create new token
        $tokenString = bin2hex(random_bytes(64));
        $expiryDays = env('TOKEN_EXPIRY_DAYS', 30);
        
        $token = static::create([
            'user_id' => $userId,
            'token' => $tokenString,
            'expires_at' => now()->addDays($expiryDays)
        ]);
        
        return $token;
    }
    
    // Verify token
    public static function verify($tokenString) {
        $token = static::where('token', $tokenString)
                      ->where('expires_at', '>', now())
                      ->first();
        
        return $token ? $token->user_id : null;
    }
    
    // Check if token is expired
    public function isExpired() {
        return $this->expires_at < now();
    }
}
?>
```

### File: `app/models/Withdrawal.php`

```php
<?php
// app/Models/Withdrawal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model {
    protected $table = 'withdrawals';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    
    // Valid methods
    const VALID_METHODS = ['dana', 'gopay', 'bank_transfer', 'ovo', 'shopeepay'];
    
    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'account_number',
        'status',
        'notes'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    // Relationships
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    // Check if status is pending
    public function isPending() {
        return $this->status === self::STATUS_PENDING;
    }
    
    // Approve withdrawal
    public function approve($notes = null) {
        $this->status = self::STATUS_APPROVED;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }
    
    // Reject withdrawal
    public function reject($notes = null) {
        $this->status = self::STATUS_REJECTED;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }
    
    // Validate method
    public static function isValidMethod($method) {
        return in_array($method, self::VALID_METHODS);
    }
}
?>


### File: `app/helpers/response.php`

```php
<?php
// app/helpers/response.php

namespace App\Helpers;

class Response {
    public static function send($success, $message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function success($message, $data = null, $statusCode = 200) {
        self::send(true, $message, $data, $statusCode);
    }
    
    public static function error($message, $statusCode = 400) {
        self::send(false, $message, null, $statusCode);
    }
    
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
    
    public static function validateRequiredFields($data, $requiredFields) {
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            self::error('Missing required fields: ' . implode(', ', $missingFields), 400);
        }
        
        return true;
    }
}
?>
```

---

## Middleware

### File: `app/middleware/auth.php`

```php
<?php
// app/middleware/auth.php

namespace App\Middleware;

use App\Models\Token;
use App\Helpers\Response;

class Auth {
    public static function authenticate() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            Response::error('Authorization header missing', 401);
        }
        
        $authHeader = $headers['Authorization'];
        
        // Expected format: Bearer <token>
        if (strpos($authHeader, 'Bearer ') !== 0) {
            Response::error('Invalid authorization format', 401);
        }
        
        $tokenString = substr($authHeader, 7);
        
        if (empty($tokenString)) {
            Response::error('Token is empty', 401);
        }
        
        $userId = Token::verify($tokenString);
        
        if (!$userId) {
            Response::error('Invalid or expired token', 401);
        }
        
        return $userId;
    }
}
?>
```

---

## Config

### File: `config/cors.php`

```php
<?php
// config/cors.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
```

---

## API Endpoints - Authentication

### File: `api/auth/register.php`

```php
<?php
// api/auth/register.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\User;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['name', 'email', 'password']);

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

// Validate password length
if (strlen($password) < 6) {
    Response::error('Password must be at least 6 characters', 400);
}

try {
    // Check if email already exists
    if (User::where('email', $email)->exists()) {
        Response::error('Email already registered', 409);
    }
    
    // Create user
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => $password // Will be hashed automatically by model
    ]);
    
    Response::success('Registration successful', [
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
    ], 201);
    
} catch (Exception $e) {
    Response::error('Registration failed: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/auth/login.php`

```php
<?php
// api/auth/login.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\User;
use App\Models\Token;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['email', 'password']);

$email = trim($data['email']);
$password = $data['password'];

try {
    // Get user by email
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        Response::error('Invalid email or password', 401);
    }
    
    // Verify password
    if (!$user->verifyPassword($password)) {
        Response::error('Invalid email or password', 401);
    }
    
    // Generate token
    $token = Token::generateToken($user->id);
    
    Response::success('Login successful', [
        'token' => $token->token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ]
    ]);
    
} catch (Exception $e) {
    Response::error('Login failed: ' . $e->getMessage(), 500);
}
?>
```

---

## API Endpoints - Goals

### File: `api/goals/index.php`

```php
<?php
// api/goals/index.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    $goals = Goal::where('user_id', $userId)
                 ->orderBy('created_at', 'desc')
                 ->get()
                 ->map(function($goal) {
                     return [
                         'id' => $goal->id,
                         'name' => $goal->name,
                         'target_amount' => (float) $goal->target_amount,
                         'current_amount' => (float) $goal->current_amount,
                         'deadline' => $goal->deadline ? $goal->deadline->format('Y-m-d') : null,
                         'description' => $goal->description,
                         'created_at' => $goal->created_at->toDateTimeString(),
                         'progress_percentage' => $goal->progress_percentage
                     ];
                 });
    
    Response::success('Goals retrieved successfully', $goals->toArray());
    
} catch (Exception $e) {
    Response::error('Failed to retrieve goals: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/goals/store.php`

```php
<?php
// api/goals/store.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['name', 'target_amount']);

$name = trim($data['name']);
$targetAmount = (float) $data['target_amount'];
$deadline = isset($data['deadline']) ? $data['deadline'] : null;
$description = isset($data['description']) ? trim($data['description']) : null;

// Validate target amount
if ($targetAmount <= 0) {
    Response::error('Target amount must be greater than 0', 400);
}

try {
    $goal = Goal::create([
        'user_id' => $userId,
        'name' => $name,
        'target_amount' => $targetAmount,
        'deadline' => $deadline,
        'description' => $description
    ]);
    
    Response::success('Goal created successfully', [
        'id' => $goal->id,
        'name' => $goal->name,
        'target_amount' => (float) $goal->target_amount,
        'current_amount' => (float) $goal->current_amount,
        'deadline' => $goal->deadline ? $goal->deadline->format('Y-m-d') : null,
        'description' => $goal->description,
        'created_at' => $goal->created_at->toDateTimeString()
    ], 201);
    
} catch (Exception $e) {
    Response::error('Failed to create goal: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/goals/update.php`

```php
<?php
// api/goals/update.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['id', 'name', 'target_amount']);

$goalId = (int) $data['id'];
$name = trim($data['name']);
$targetAmount = (float) $data['target_amount'];
$deadline = isset($data['deadline']) ? $data['deadline'] : null;
$description = isset($data['description']) ? trim($data['description']) : null;

// Validate target amount
if ($targetAmount <= 0) {
    Response::error('Target amount must be greater than 0', 400);
}

try {
    // Find goal and check ownership
    $goal = Goal::where('id', $goalId)
                ->where('user_id', $userId)
                ->first();
    
    if (!$goal) {
        Response::error('Goal not found or access denied', 404);
    }
    
    // Update goal
    $goal->update([
        'name' => $name,
        'target_amount' => $targetAmount,
        'deadline' => $deadline,
        'description' => $description
    ]);
    
    Response::success('Goal updated successfully', [
        'id' => $goal->id,
        'name' => $goal->name,
        'target_amount' => (float) $goal->target_amount,
        'current_amount' => (float) $goal->current_amount,
        'deadline' => $goal->deadline ? $goal->deadline->format('Y-m-d') : null,
        'description' => $goal->description,
        'updated_at' => $goal->updated_at->toDateTimeString()
    ]);
    
} catch (Exception $e) {
    Response::error('Failed to update goal: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/goals/delete.php`

```php
<?php
// api/goals/delete.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['id']);

$goalId = (int) $data['id'];

try {
    // Find goal and check ownership
    $goal = Goal::where('id', $goalId)
                ->where('user_id', $userId)
                ->first();
    
    if (!$goal) {
        Response::error('Goal not found or access denied', 404);
    }
    
    // Delete goal (cascade will delete transactions via database)
    $goal->delete();
    
    Response::success('Goal deleted successfully');
    
} catch (Exception $e) {
    Response::error('Failed to delete goal: ' . $e->getMessage(), 500);
}
?>
```

---

## API Endpoints - Transactions

### File: `api/transactions/index.php`

```php
<?php
// api/transactions/index.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

// Get goal_id from query parameter
$goalId = isset($_GET['goal_id']) ? (int) $_GET['goal_id'] : null;

if (!$goalId) {
    Response::error('goal_id parameter is required', 400);
}

try {
    // Check if goal belongs to user
    $goal = Goal::where('id', $goalId)
                ->where('user_id', $userId)
                ->first();
    
    if (!$goal) {
        Response::error('Goal not found or access denied', 404);
    }
    
    // Get transactions
    $transactions = Transaction::where('goal_id', $goalId)
                              ->orderBy('transaction_date', 'desc')
                              ->orderBy('created_at', 'desc')
                              ->get()
                              ->map(function($transaction) {
                                  return [
                                      'id' => $transaction->id,
                                      'goal_id' => $transaction->goal_id,
                                      'amount' => (float) $transaction->amount,
                                      'description' => $transaction->description,
                                      'transaction_date' => $transaction->transaction_date->toDateTimeString(),
                                      'created_at' => $transaction->created_at->toDateTimeString()
                                  ];
                              });
    
    Response::success('Transactions retrieved successfully', $transactions->toArray());
    
} catch (Exception $e) {
    Response::error('Failed to retrieve transactions: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/transactions/store.php`

```php
<?php
// api/transactions/store.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['goal_id', 'amount']);

$goalId = (int) $data['goal_id'];
$amount = (float) $data['amount'];
$description = isset($data['description']) ? trim($data['description']) : null;

// Validate amount
if ($amount <= 0) {
    Response::error('Amount must be greater than 0', 400);
}

try {
    // Check if goal exists and belongs to user
    $goal = Goal::where('id', $goalId)
                ->where('user_id', $userId)
                ->first();
    
    if (!$goal) {
        Response::error('Goal not found or access denied', 404);
    }
    
    // Create transaction (will auto-update goal amount via model event)
    $transaction = Transaction::create([
        'goal_id' => $goalId,
        'amount' => $amount,
        'description' => $description,
        'transaction_date' => now()
    ]);
    
    Response::success('Transaction created successfully', [
        'id' => $transaction->id,
        'goal_id' => $transaction->goal_id,
        'amount' => (float) $transaction->amount,
        'description' => $transaction->description,
        'transaction_date' => $transaction->transaction_date->toDateTimeString(),
        'created_at' => $transaction->created_at->toDateTimeString()
    ], 201);
    
} catch (Exception $e) {
    Response::error('Failed to create transaction: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/transactions/delete.php`

```php
<?php
// api/transactions/delete.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Transaction;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['id']);

$transactionId = (int) $data['id'];

try {
    // Get transaction with goal relationship
    $transaction = Transaction::with('goal')
                              ->find($transactionId);
    
    if (!$transaction) {
        Response::error('Transaction not found', 404);
    }
    
    // Check if goal belongs to user
    if ($transaction->goal->user_id != $userId) {
        Response::error('Access denied', 403);
    }
    
    // Delete transaction (will auto-update goal amount via model event)
    $transaction->delete();
    
    Response::success('Transaction deleted successfully');
    
} catch (Exception $e) {
    Response::error('Failed to delete transaction: ' . $e->getMessage(), 500);
}
?>
```

---

---

## API Endpoints - Withdrawals

### File: `api/withdrawals/request.php`

```php
<?php
// api/withdrawals/request.php
// POST /api/withdrawals/request - User minta penarikan

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Withdrawal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['amount', 'method']);

$amount = (float) $data['amount'];
$method = strtolower(trim($data['method']));
$accountNumber = isset($data['account_number']) ? trim($data['account_number']) : null;
$notes = isset($data['notes']) ? trim($data['notes']) : null;

// Validate amount
if ($amount <= 0) {
    Response::error('Amount must be greater than 0', 400);
}

// Validate method
if (!Withdrawal::isValidMethod($method)) {
    Response::error('Invalid method. Valid methods are: ' . implode(', ', Withdrawal::VALID_METHODS), 400);
}

try {
    // Calculate user's total balance from all goals
    $totalBalance = Goal::where('user_id', $userId)->sum('current_amount');
    
    // Validate sufficient balance
    if ($totalBalance < $amount) {
        Response::error('Insufficient balance. Your total savings: Rp ' . number_format($totalBalance, 2), 400);
    }
    
    // Create withdrawal request
    $withdrawal = Withdrawal::create([
        'user_id' => $userId,
        'amount' => $amount,
        'method' => $method,
        'account_number' => $accountNumber,
        'status' => Withdrawal::STATUS_PENDING,
        'notes' => $notes
    ]);
    
    Response::success('Withdrawal request submitted successfully', [
        'id' => $withdrawal->id,
        'amount' => (float) $withdrawal->amount,
        'method' => $withdrawal->method,
        'account_number' => $withdrawal->account_number,
        'status' => $withdrawal->status,
        'notes' => $withdrawal->notes,
        'total_balance' => (float) $totalBalance,
        'created_at' => $withdrawal->created_at->toDateTimeString()
    ], 201);
    
} catch (Exception $e) {
    Response::error('Failed to create withdrawal request: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/withdrawals/index.php`

```php
<?php
// api/withdrawals/index.php
// GET /api/withdrawals/index - Lihat history withdrawal

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Withdrawal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    // Build query
    $query = Withdrawal::where('user_id', $userId);
    
    // Filter by status if provided
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status = strtolower(trim($_GET['status']));
        $validStatuses = ['pending', 'approved', 'rejected'];
        
        if (in_array($status, $validStatuses)) {
            $query->where('status', $status);
        }
    }
    
    // Get withdrawals ordered by created_at desc
    $withdrawals = $query->orderBy('created_at', 'desc')->get();
    
    // Format response
    $formattedWithdrawals = [];
    foreach ($withdrawals as $withdrawal) {
        $formattedWithdrawals[] = [
            'id' => $withdrawal->id,
            'amount' => (float) $withdrawal->amount,
            'method' => $withdrawal->method,
            'account_number' => $withdrawal->account_number,
            'status' => $withdrawal->status,
            'notes' => $withdrawal->notes,
            'created_at' => $withdrawal->created_at->toDateTimeString(),
            'updated_at' => $withdrawal->updated_at ? $withdrawal->updated_at->toDateTimeString() : null
        ];
    }
    
    // Calculate summary
    $summary = [
        'total_requests' => count($formattedWithdrawals),
        'pending_count' => Withdrawal::where('user_id', $userId)->where('status', 'pending')->count(),
        'approved_count' => Withdrawal::where('user_id', $userId)->where('status', 'approved')->count(),
        'rejected_count' => Withdrawal::where('user_id', $userId)->where('status', 'rejected')->count()
    ];
    
    Response::success('Withdrawals retrieved successfully', [
        'summary' => $summary,
        'withdrawals' => $formattedWithdrawals
    ]);
    
} catch (Exception $e) {
    Response::error('Failed to retrieve withdrawals: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/withdrawals/approve.php`

```php
<?php
// api/withdrawals/approve.php
// POST /api/withdrawals/approve - Admin approve/reject withdrawal (Dummy - no role check)

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Withdrawal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Note: In production, add admin role check here
$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['withdrawal_id']);

$withdrawalId = (int) $data['withdrawal_id'];
$action = isset($data['action']) ? strtolower(trim($data['action'])) : 'approve';
$notes = isset($data['notes']) ? trim($data['notes']) : null;

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    Response::error('Invalid action. Valid actions are: approve, reject', 400);
}

try {
    // Find withdrawal
    $withdrawal = Withdrawal::find($withdrawalId);
    
    if (!$withdrawal) {
        Response::error('Withdrawal not found', 404);
    }
    
    // Check if already processed
    if (!$withdrawal->isPending()) {
        Response::error('Withdrawal has already been processed. Current status: ' . $withdrawal->status, 400);
    }
    
    if ($action === 'approve') {
        // Check if user has sufficient balance
        $totalBalance = Goal::where('user_id', $withdrawal->user_id)->sum('current_amount');
        
        if ($totalBalance < $withdrawal->amount) {
            Response::error('User has insufficient balance for this withdrawal', 400);
        }
        
        // Deduct from user's goals (proportionally from each goal)
        $amountToDeduct = $withdrawal->amount;
        $goals = Goal::where('user_id', $withdrawal->user_id)
                     ->where('current_amount', '>', 0)
                     ->orderBy('current_amount', 'desc')
                     ->get();
        
        foreach ($goals as $goal) {
            if ($amountToDeduct <= 0) break;
            
            $deductFromThisGoal = min($goal->current_amount, $amountToDeduct);
            $goal->subtractAmount($deductFromThisGoal);
            $amountToDeduct -= $deductFromThisGoal;
        }
        
        // Approve withdrawal
        $withdrawal->approve($notes ?? 'Withdrawal approved');
        
        Response::success('Withdrawal approved successfully', [
            'id' => $withdrawal->id,
            'amount' => (float) $withdrawal->amount,
            'method' => $withdrawal->method,
            'status' => $withdrawal->status,
            'notes' => $withdrawal->notes,
            'updated_at' => $withdrawal->updated_at->toDateTimeString()
        ]);
        
    } else {
        // Reject withdrawal
        $withdrawal->reject($notes ?? 'Withdrawal rejected');
        
        Response::success('Withdrawal rejected', [
            'id' => $withdrawal->id,
            'amount' => (float) $withdrawal->amount,
            'status' => $withdrawal->status,
            'notes' => $withdrawal->notes,
            'updated_at' => $withdrawal->updated_at->toDateTimeString()
        ]);
    }
    
} catch (Exception $e) {
    Response::error('Failed to process withdrawal: ' . $e->getMessage(), 500);
}
?>
```

---

## API Endpoints - Profile & Dashboard

### File: `api/profile/user.php`

```php
<?php
// api/profile/user.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\User;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    $user = User::find($userId);
    
    if (!$user) {
        Response::error('User not found', 404);
    }
    
    Response::success('User profile retrieved successfully', [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'created_at' => $user->created_at->toDateTimeString()
    ]);
    
} catch (Exception $e) {
    Response::error('Failed to retrieve user profile: ' . $e->getMessage(), 500);
}
?>
```

### File: `api/dashboard/summary.php`

```php
<?php
// api/dashboard/summary.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    // Get all goals for this user
    $goals = Goal::where('user_id', $userId)->get();
    
    // Calculate statistics
    $totalGoals = $goals->count();
    $totalSaved = $goals->sum('current_amount');
    $totalTarget = $goals->sum('target_amount');
    $completedGoals = $goals->filter(function($goal) {
        return $goal->current_amount >= $goal->target_amount;
    })->count();
    
    $activeGoals = $totalGoals - $completedGoals;
    $overallProgress = $totalTarget > 0 ? round(($totalSaved / $totalTarget) * 100, 2) : 0;
    
    // Get nearest goal to completion (highest percentage, not yet completed)
    $nearestGoal = $goals->filter(function($goal) {
                         return $goal->current_amount < $goal->target_amount;
                     })
                     ->sortByDesc(function($goal) {
                         return $goal->progress_percentage;
                     })
                     ->first();
    
    $nearestGoalData = null;
    if ($nearestGoal) {
        $nearestGoalData = [
            'id' => $nearestGoal->id,
            'name' => $nearestGoal->name,
            'target_amount' => (float) $nearestGoal->target_amount,
            'current_amount' => (float) $nearestGoal->current_amount,
            'deadline' => $nearestGoal->deadline ? $nearestGoal->deadline->format('Y-m-d') : null,
            'progress_percentage' => $nearestGoal->progress_percentage
        ];
    }
    
    $summary = [
        'total_goals' => $totalGoals,
        'total_saved' => (float) $totalSaved,
        'total_target' => (float) $totalTarget,
        'completed_goals' => $completedGoals,
        'active_goals' => $activeGoals,
        'overall_progress' => $overallProgress,
        'nearest_goal' => $nearestGoalData
    ];
    
    Response::success('Dashboard summary retrieved successfully', $summary);
    
} catch (Exception $e) {
    Response::error('Failed to retrieve dashboard summary: ' . $e->getMessage(), 500);
}
?>
```

---

## Utilities

### File: `.htaccess`

```apache
# .htaccess for GoalMoney API

# Enable rewrite engine
RewriteEngine On

# Handle Authorization Header
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

# Allow CORS preflight requests
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Prevent directory listing
Options -Indexes

# Set default charset
AddDefaultCharset UTF-8

# Error pages (optional)
ErrorDocument 404 /index.php
ErrorDocument 500 /index.php
```

### File: `.gitignore`

```gitignore
# Environment variables
.env

# Composer
/vendor/
composer.lock

# IDE
.vscode/
.idea/
*.sublime-project
*.sublime-workspace

# OS
.DS_Store
Thumbs.db

# Logs
*.log
error_log

# Temporary files
*.tmp
*.temp
*.swp
*.swo
*~

# Cache
cache/
*.cache
```

---

## Migration & Seeder Scripts

### File: `migrations/create_tables.php`

```php
<?php
// migrations/create_tables.php
// Script untuk membuat tabel menggunakan Eloquent Schema Builder

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Running migrations...\n\n";

try {
    // Create users table
    if (!Capsule::schema()->hasTable('users')) {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->timestamps();
        });
        echo "✓ Table 'users' created\n";
    } else {
        echo "✓ Table 'users' already exists\n";
    }

    // Create goals table
    if (!Capsule::schema()->hasTable('goals')) {
        Capsule::schema()->create('goals', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name', 100);
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            $table->index('user_id');
        });
        echo "✓ Table 'goals' created\n";
    } else {
        echo "✓ Table 'goals' already exists\n";
    }

    // Create transactions table
    if (!Capsule::schema()->hasTable('transactions')) {
        Capsule::schema()->create('transactions', function ($table) {
            $table->increments('id');
            $table->integer('goal_id')->unsigned();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('goal_id')
                  ->references('id')
                  ->on('goals')
                  ->onDelete('cascade');
            
            $table->index('goal_id');
        });
        echo "✓ Table 'transactions' created\n";
    } else {
        echo "✓ Table 'transactions' already exists\n";
    }

    // Create tokens table
    if (!Capsule::schema()->hasTable('tokens')) {
        Capsule::schema()->create('tokens', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('token', 255)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            $table->index('token');
            $table->index('user_id');
        });
        echo "✓ Table 'tokens' created\n";
    } else {
        echo "✓ Table 'tokens' already exists\n";
    }

    echo "\n✅ All migrations completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
```

### File: `seeders/seed_data.php`

```php
<?php
// seeders/seed_data.php
// Script untuk generate dummy data untuk testing

require_once __DIR__ . '/../bootstrap.php';

use App\Models\User;
use App\Models\Goal;
use App\Models\Transaction;

echo "Seeding database...\n\n";

try {
    // Create test user
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@goalmoney.com',
        'password' => 'password123'
    ]);
    echo "✓ User created: {$user->email}\n";

    // Create goals
    $goals = [
        [
            'name' => 'Laptop Baru',
            'target_amount' => 15000000,
            'deadline' => date('Y-m-d', strtotime('+6 months')),
            'description' => 'Untuk kuliah dan kerja'
        ],
        [
            'name' => 'Liburan ke Bali',
            'target_amount' => 10000000,
            'deadline' => date('Y-m-d', strtotime('+1 year')),
            'description' => 'Budget liburan keluarga'
        ],
        [
            'name' => 'Emergency Fund',
            'target_amount' => 30000000,
            'deadline' => null,
            'description' => 'Dana darurat 6 bulan pengeluaran'
        ]
    ];

    foreach ($goals as $goalData) {
        $goal = Goal::create([
            'user_id' => $user->id,
            'name' => $goalData['name'],
            'target_amount' => $goalData['target_amount'],
            'deadline' => $goalData['deadline'],
            'description' => $goalData['description']
        ]);
        
        echo "✓ Goal created: {$goal->name}\n";
        
        // Add some transactions for each goal
        $transactionCount = rand(2, 5);
        for ($i = 0; $i < $transactionCount; $i++) {
            $amount = rand(100000, 2000000);
            Transaction::create([
                'goal_id' => $goal->id,
                'amount' => $amount,
                'description' => 'Setoran ke-' . ($i + 1),
                'transaction_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
            ]);
        }
        
        echo "  ✓ Added {$transactionCount} transactions\n";
    }

    echo "\n✅ Seeding completed successfully!\n";
    echo "\n📧 Test account:\n";
    echo "   Email: test@goalmoney.com\n";
    echo "   Password: password123\n";

} catch (Exception $e) {
    echo "\n❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
```

---

## 📚 Installation Guide

### 1. Install Dependencies

```bash
composer install
```

### 2. Setup Environment

```bash
cp .env.example .env
# Edit .env with your database credentials
```

### 3. Create Database

```bash
createdb goalmoney_db
# or
psql -U postgres -c "CREATE DATABASE goalmoney_db;"
```

### 4. Run Migrations

```bash
# Option A: Using SQL file
psql -U postgres -d goalmoney_db -f database_schema.sql

# Option B: Using PHP migration script
php migrations/create_tables.php
```

### 5. (Optional) Seed Test Data

```bash
php seeders/seed_data.php
```

### 6. Test API

```bash
curl http://localhost/goalmoney-api/
```

---

## 🔧 Project Structure

```
goalmoney-api/
├── api/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── goals/
│   │   ├── index.php
│   │   ├── store.php
│   │   ├── update.php
│   │   └── delete.php
│   ├── transactions/
│   │   ├── index.php
│   │   ├── store.php
│   │   └── delete.php
│   ├── profile/
│   │   └── user.php
│   └── dashboard/
│       └── summary.php
├── app/
│   ├── models/
│   │   ├── User.php
│   │   ├── Goal.php
│   │   ├── Transaction.php
│   │   └── Token.php
│   ├── middleware/
│   │   └── auth.php
│   └── helpers/
│       └── response.php
├── config/
│   └── cors.php
├── migrations/
│   └── create_tables.php
├── seeders/
│   └── seed_data.php
├── vendor/
├── .env
├── .env.example
├── .gitignore
├── .htaccess
├── bootstrap.php
├── composer.json
└── index.php
```

---

## ✅ Features

- ✅ Eloquent ORM for database operations
- ✅ Environment variables for configuration
- ✅ Bearer token authentication
- ✅ Password hashing with bcrypt
- ✅ Auto-update goal amounts via model events
- ✅ RESTful API design
- ✅ CORS enabled
- ✅ Input validation
- ✅ Error handling
- ✅ Migration & seeder scripts

---

## 📖 API Documentation

All endpoints and usage examples are documented in the README.md file.

---

## 🔒 Security Features

1. **Password Hashing**: Bcrypt via Eloquent
2. **Token Authentication**: Bearer tokens with expiration
3. **SQL Injection Protection**: Eloquent ORM
4. **Environment Variables**: Sensitive data not hardcoded
5. **CORS Configuration**: Controlled access
6. **Input Validation**: All inputs validated

---

**End of Documentation**