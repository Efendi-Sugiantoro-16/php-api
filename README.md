# GoalMoney API Backend

**Goal-Based Savings Application Backend**

REST API backend untuk aplikasi GoalMoney yang dibangun dengan PHP menggunakan Eloquent ORM.

---

## 📋 Daftar Isi

1. [Pendahuluan](#-pendahuluan)
2. [Persyaratan Sistem](#-persyaratan-sistem)
3. [Instalasi](#-instalasi)
4. [Konfigurasi](#-konfigurasi)
5. [Struktur Proyek](#-struktur-proyek)
6. [API Endpoints](#-api-endpoints)
7. [Database Schema](#-database-schema)
8. [Authentication](#-authentication)
9. [Fitur Premium](#-fitur-premium)
10. [Troubleshooting](#-troubleshooting)

---

## 📖 Pendahuluan

GoalMoney API adalah backend REST API untuk aplikasi tabungan berbasis goal. API ini menangani:

- **Autentikasi** - Register, Login, Logout dengan JWT Token
- **Goals Management** - CRUD operasi untuk goal tabungan
- **Transactions** - Deposit ke goal
- **Withdrawals** - Penarikan saldo
- **Reports** - Laporan tabungan lengkap
- **Badges** - Sistem gamifikasi
- **Analytics** - Statistik dan rekomendasi

---

## 💻 Persyaratan Sistem

| Komponen   | Versi Minimum |
| ---------- | ------------- |
| PHP        | 8.0+          |
| PostgreSQL | 12+           |
| Composer   | 2.0+          |

### Extension PHP yang Diperlukan

- `pdo_pgsql`
- `mbstring`
- `json`
- `openssl`

---

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd php-api
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
```

Edit file `.env`:

```env
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=goalmoney
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 4. Buat Database

```bash
# Login ke PostgreSQL
psql -U postgres

# Buat database
CREATE DATABASE goalmoney;
\q
```

### 5. Jalankan Migrasi

```bash
php run_migrations.php
```

### 6. Jalankan Server

```bash
# Development
php -S 0.0.0.0:8000 index.php

# Atau gunakan Laragon/XAMPP
```

---

## ⚙️ Konfigurasi

### File `.env`

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=goalmoney
DB_USERNAME=postgres
DB_PASSWORD=password

# Firebase (Optional - untuk Push Notifications)
FIREBASE_SERVER_KEY=your_firebase_key
```

### File `config/cors.php`

CORS sudah dikonfigurasi untuk menerima request dari semua origin. Untuk production, batasi origin:

```php
header("Access-Control-Allow-Origin: https://yourdomain.com");
```

---

## 📁 Struktur Proyek

```
php-api/
├── api/                    # API Endpoints
│   ├── auth/               # Autentikasi
│   │   ├── login.php
│   │   ├── register.php
│   │   └── logout.php
│   ├── goals/              # Manajemen Goal
│   │   ├── index.php
│   │   ├── store.php
│   │   ├── update.php
│   │   └── delete.php
│   ├── transactions/       # Transaksi Deposit
│   │   ├── index.php
│   │   ├── store.php
│   │   ├── allocate.php
│   │   └── delete.php
│   ├── withdrawals/        # Penarikan
│   │   ├── index.php
│   │   ├── request.php
│   │   └── approve.php
│   ├── dashboard/          # Dashboard Summary
│   │   └── summary.php
│   ├── reports/            # Laporan
│   │   └── report.php
│   ├── badges/             # Sistem Badge
│   │   ├── index.php
│   │   └── check.php
│   ├── analytics/          # Analytics
│   │   ├── summary.php
│   │   └── streak.php
│   ├── recommendations/    # Smart Recommendation
│   │   └── index.php
│   ├── profile/            # Profil User
│   │   └── update.php
│   └── notifications/      # Notifikasi
│       └── index.php
├── app/                    # Application Core
│   ├── Models/             # Eloquent Models
│   │   ├── User.php
│   │   ├── Goal.php
│   │   ├── Transaction.php
│   │   ├── Withdrawal.php
│   │   ├── Badge.php
│   │   ├── UserBadge.php
│   │   ├── Token.php
│   │   └── Notification.php
│   ├── Helpers/            # Helper Classes
│   │   └── Response.php
│   └── Middleware/         # Middleware
│       └── Auth.php
├── config/                 # Konfigurasi
│   └── cors.php
├── migrations/             # Database Migrations
│   ├── create_tables.php
│   ├── create_badges_tables.php
│   └── ...
├── bootstrap.php           # Bootstrap Aplikasi
├── index.php               # Entry Point & Router
├── .env                    # Environment Variables
└── composer.json           # Dependencies
```

---

## 🔌 API Endpoints

### Authentication

| Method | Endpoint         | Deskripsi        |
| ------ | ---------------- | ---------------- |
| POST   | `/auth/register` | Daftar akun baru |
| POST   | `/auth/login`    | Login            |
| POST   | `/auth/logout`   | Logout           |

#### Register

```http
POST /auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

#### Login

```http
POST /auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "secret123"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### Goals

| Method | Endpoint                | Deskripsi        |
| ------ | ----------------------- | ---------------- |
| GET    | `/goals`                | List semua goals |
| POST   | `/goals/store`          | Buat goal baru   |
| PUT    | `/goals/update?id={id}` | Update goal      |
| DELETE | `/goals/delete?id={id}` | Hapus goal       |

#### Create Goal

```http
POST /goals/store
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Beli Laptop",
  "target_amount": 10000000,
  "deadline": "2024-12-31",
  "description": "MacBook Pro M3",
  "type": "digital"
}
```

---

### Transactions

| Method | Endpoint                     | Deskripsi           |
| ------ | ---------------------------- | ------------------- |
| GET    | `/transactions?goal_id={id}` | List transaksi goal |
| POST   | `/transactions/store`        | Deposit ke goal     |
| POST   | `/transactions/allocate`     | Alokasi overflow    |

#### Deposit

```http
POST /transactions/store
Authorization: Bearer {token}
Content-Type: application/json

{
  "goal_id": 1,
  "amount": 500000,
  "method": "transfer",
  "description": "Gajian bulan ini"
}
```

---

### Withdrawals

| Method | Endpoint               | Deskripsi                  |
| ------ | ---------------------- | -------------------------- |
| GET    | `/withdrawals`         | List withdrawal history    |
| POST   | `/withdrawals/request` | Request withdrawal         |
| POST   | `/withdrawals/approve` | Approve withdrawal (admin) |

#### Request Withdrawal

```http
POST /withdrawals/request
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 500000,
  "method": "dana",
  "account_number": "08123456789",
  "notes": "Ambil sebagian"
}
```

---

### Dashboard

| Method | Endpoint             | Deskripsi         |
| ------ | -------------------- | ----------------- |
| GET    | `/dashboard/summary` | Summary dashboard |

**Response:**

```json
{
  "success": true,
  "data": {
    "total_saved": 5000000,
    "total_target": 10000000,
    "overall_progress": 50.0,
    "active_goals": 3,
    "completed_goals": 1,
    "available_balance": 250000
  }
}
```

---

### Reports

| Method | Endpoint          | Deskripsi       |
| ------ | ----------------- | --------------- |
| GET    | `/reports/report` | Laporan lengkap |

---

### Badges (Gamifikasi)

| Method | Endpoint        | Deskripsi            |
| ------ | --------------- | -------------------- |
| GET    | `/badges`       | List semua badges    |
| POST   | `/badges/check` | Check & award badges |

---

### Analytics

| Method | Endpoint                         | Deskripsi            |
| ------ | -------------------------------- | -------------------- |
| GET    | `/analytics/summary?year={year}` | Summary analytics    |
| GET    | `/analytics/streak?year={year}`  | Streak calendar data |

---

### Recommendations

| Method | Endpoint           | Deskripsi             |
| ------ | ------------------ | --------------------- |
| GET    | `/recommendations` | Smart recommendations |

---

## 🗄️ Database Schema

### Tabel `users`

| Kolom             | Tipe          | Deskripsi       |
| ----------------- | ------------- | --------------- |
| id                | SERIAL        | Primary key     |
| name              | VARCHAR(100)  | Nama user       |
| email             | VARCHAR(100)  | Email (unique)  |
| password          | VARCHAR(255)  | Password hashed |
| available_balance | DECIMAL(15,2) | Saldo tersedia  |
| created_at        | TIMESTAMP     | Waktu buat      |
| updated_at        | TIMESTAMP     | Waktu update    |

### Tabel `goals`

| Kolom          | Tipe          | Deskripsi            |
| -------------- | ------------- | -------------------- |
| id             | SERIAL        | Primary key          |
| user_id        | INT           | Foreign key ke users |
| name           | VARCHAR(100)  | Nama goal            |
| target_amount  | DECIMAL(15,2) | Target tabungan      |
| current_amount | DECIMAL(15,2) | Tabungan saat ini    |
| deadline       | DATE          | Deadline goal        |
| description    | TEXT          | Deskripsi            |
| type           | VARCHAR(20)   | 'digital' / 'cash'   |

### Tabel `transactions`

| Kolom            | Tipe          | Deskripsi            |
| ---------------- | ------------- | -------------------- |
| id               | SERIAL        | Primary key          |
| goal_id          | INT           | Foreign key ke goals |
| amount           | DECIMAL(15,2) | Jumlah deposit       |
| method           | VARCHAR(50)   | Metode deposit       |
| description      | TEXT          | Deskripsi            |
| transaction_date | TIMESTAMP     | Tanggal transaksi    |

### Tabel `withdrawals`

| Kolom          | Tipe          | Deskripsi                       |
| -------------- | ------------- | ------------------------------- |
| id             | SERIAL        | Primary key                     |
| user_id        | INT           | Foreign key ke users            |
| goal_id        | INT           | Foreign key ke goals (nullable) |
| amount         | DECIMAL(15,2) | Jumlah penarikan                |
| method         | VARCHAR(50)   | dana/gopay/bank/ovo             |
| account_number | VARCHAR(50)   | Nomor akun tujuan               |
| status         | VARCHAR(20)   | pending/approved/rejected       |
| notes          | TEXT          | Catatan                         |

### Tabel `badges`

| Kolom             | Tipe         | Deskripsi         |
| ----------------- | ------------ | ----------------- |
| id                | SERIAL       | Primary key       |
| code              | VARCHAR(50)  | Unique code       |
| name              | VARCHAR(100) | Nama badge        |
| description       | TEXT         | Deskripsi         |
| icon              | VARCHAR(10)  | Emoji icon        |
| requirement_type  | VARCHAR(50)  | Tipe requirement  |
| requirement_value | INT          | Nilai requirement |

### Tabel `user_badges`

| Kolom     | Tipe      | Deskripsi             |
| --------- | --------- | --------------------- |
| id        | SERIAL    | Primary key           |
| user_id   | INT       | Foreign key ke users  |
| badge_id  | INT       | Foreign key ke badges |
| earned_at | TIMESTAMP | Waktu diperoleh       |

---

## 🔐 Authentication

API menggunakan **Bearer Token** untuk autentikasi.

### Cara Penggunaan

1. Login untuk mendapatkan token
2. Sertakan token di header setiap request:

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Middleware Auth

File: `app/Middleware/Auth.php`

```php
$userId = Auth::authenticate();
```

Akan return `user_id` jika valid, atau mengirim error 401 jika tidak.

---

## 🏆 Fitur Premium

### 1. Badge System

16 badges tersedia dengan berbagai requirement:

- **First Saver** - Deposit pertama
- **Week Warrior** - Streak 7 hari
- **Millionaire** - Total Rp 1.000.000
- Dan lainnya...

### 2. Streak Calendar

API menghitung:

- Current streak (hari berturut-turut nabung)
- Longest streak
- Calendar heatmap data

### 3. Smart Recommendations

Logika rekomendasi:

```
daily_suggestion = remaining_amount / days_remaining
```

Urgency levels:

- **Critical**: < 7 hari
- **High**: < 30 hari
- **Medium**: < 90 hari
- **Normal**: > 90 hari

### 4. Analytics Dashboard

Data agregasi:

- Monthly savings trend
- Goal category distribution
- Goal progress comparison

---

## 🐛 Troubleshooting

### Error: Database Connection Failed

```
Pastikan:
1. PostgreSQL service running
2. Database exists
3. Credentials di .env benar
```

### Error: 401 Unauthorized

```
Pastikan:
1. Token valid dan belum expired
2. Header "Authorization: Bearer {token}" ada
3. Token format benar
```

### Error: 500 Internal Server Error

```
Cek:
1. PHP error log
2. File permissions
3. Extension PHP yang diperlukan
```

---

## 📝 Contoh Response

### Success Response

```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message here",
  "errors": { ... }
}
```

---

## 📞 Kontak

Untuk pertanyaan atau bantuan, hubungi tim development.

---

**© 2024 GoalMoney. All rights reserved.**
