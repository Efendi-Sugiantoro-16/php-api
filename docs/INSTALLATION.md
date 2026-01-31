# Panduan Instalasi - GoalMoney API

Dokumentasi lengkap langkah-langkah instalasi GoalMoney PHP API Backend.

---

## 📋 Daftar Isi

1. [Persyaratan](#1-persyaratan)
2. [Instalasi Development](#2-instalasi-development)
3. [Instalasi Production](#3-instalasi-production)
4. [Konfigurasi Database](#4-konfigurasi-database)
5. [Running Migrations](#5-running-migrations)
6. [Testing API](#6-testing-api)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. Persyaratan

### Software Requirements

| Software   | Versi  | Keterangan           |
| ---------- | ------ | -------------------- |
| PHP        | 8.0+   | Dengan extension PDO |
| PostgreSQL | 12+    | Database server      |
| Composer   | 2.0+   | PHP package manager  |
| Git        | Latest | Version control      |

### PHP Extensions

```
pdo_pgsql    - PostgreSQL driver
mbstring     - Multibyte string support
json         - JSON support
openssl      - Encryption
```

### Cek PHP Extensions

```bash
php -m | grep -E "pdo_pgsql|mbstring|json|openssl"
```

---

## 2. Instalasi Development

### Step 1: Clone Repository

```bash
git clone <repository-url>
cd php-api
```

### Step 2: Install Dependencies

```bash
composer install
```

### Step 3: Buat File Environment

```bash
cp .env.example .env
```

### Step 4: Edit Konfigurasi

Buka file `.env` dengan text editor:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=goalmoney
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Optional: Firebase
FIREBASE_SERVER_KEY=
```

### Step 5: Buat Database

#### Menggunakan psql (Command Line):

```bash
# Login ke PostgreSQL
psql -U postgres

# Buat database
CREATE DATABASE goalmoney;

# Verifikasi
\l

# Keluar
\q
```

#### Menggunakan pgAdmin:

1. Buka pgAdmin
2. Klik kanan pada "Databases"
3. Pilih "Create" → "Database"
4. Nama: `goalmoney`
5. Klik "Save"

### Step 6: Jalankan Migrasi

```bash
php run_migrations.php
```

### Step 7: Jalankan Server Development

```bash
php -S 0.0.0.0:8000 index.php
```

API akan berjalan di `http://localhost:8000`

---

## 3. Instalasi Production

### Menggunakan Apache

#### Step 1: Virtual Host Configuration

```apache
<VirtualHost *:80>
    ServerName api.goalmoney.com
    DocumentRoot /var/www/goalmoney-api

    <Directory /var/www/goalmoney-api>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/goalmoney-error.log
    CustomLog ${APACHE_LOG_DIR}/goalmoney-access.log combined
</VirtualHost>
```

#### Step 2: .htaccess

Buat file `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
Header set X-XSS-Protection "1; mode=block"
```

#### Step 3: Enable Site

```bash
sudo a2ensite goalmoney.conf
sudo a2enmod rewrite headers
sudo systemctl restart apache2
```

### Menggunakan Nginx

```nginx
server {
    listen 80;
    server_name api.goalmoney.com;
    root /var/www/goalmoney-api;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Menggunakan Laragon (Windows)

1. Copy folder `php-api` ke `C:\laragon\www\`
2. Start Laragon
3. Akses via `http://php-api.test` atau `http://localhost/php-api`

---

## 4. Konfigurasi Database

### File `.env`

```env
# Development
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=goalmoney
DB_USERNAME=postgres
DB_PASSWORD=password

# Production (contoh)
DB_HOST=db.server.com
DB_PORT=5432
DB_DATABASE=goalmoney_prod
DB_USERNAME=goalmoney_user
DB_PASSWORD=securepassword123
```

### Menggunakan Database URL

```env
DATABASE_URL=postgresql://user:password@host:5432/database
```

### SSL Connection (Production)

```env
DB_SSLMODE=require
```

---

## 5. Running Migrations

### Jalankan Semua Migrasi

```bash
php run_migrations.php
```

### Jalankan Migrasi Spesifik

```bash
php migrations/create_tables.php
php migrations/create_badges_tables.php
```

### Verifikasi Tabel

```bash
psql -U postgres -d goalmoney

# List semua tabel
\dt

# Verifikasi struktur
\d users
\d goals
\d badges
```

### Expected Tables

| Tabel         | Deskripsi       |
| ------------- | --------------- |
| users         | Data pengguna   |
| tokens        | Auth tokens     |
| goals         | Goal tabungan   |
| transactions  | Deposit history |
| withdrawals   | Penarikan dana  |
| badges        | Master badges   |
| user_badges   | Badge per user  |
| notifications | Notifikasi      |

---

## 6. Testing API

### Menggunakan cURL

#### Test Health Check

```bash
curl http://localhost:8000/
```

#### Test Register

```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'
```

#### Test Login

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

### Menggunakan Postman

1. Import collection dari `docs/postman_collection.json` (jika ada)
2. Set environment variable `{{base_url}}` = `http://localhost:8000`
3. Setelah login, set `{{token}}` dari response

### Menggunakan VS Code REST Client

Buat file `test.http`:

```http
### Register
POST http://localhost:8000/auth/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

### Login
POST http://localhost:8000/auth/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}

### Get Goals (dengan token)
GET http://localhost:8000/goals
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 7. Troubleshooting

### Error: Database Connection Failed

**Gejala:**

```
SQLSTATE[08006] [7] could not connect to server
```

**Solusi:**

1. Pastikan PostgreSQL service running:

   ```bash
   # Linux
   sudo systemctl status postgresql
   sudo systemctl start postgresql

   # Windows (Laragon)
   # Start dari Laragon GUI
   ```

2. Verifikasi credentials di `.env`

3. Test koneksi manual:
   ```bash
   psql -h localhost -U postgres -d goalmoney
   ```

### Error: Class Not Found

**Gejala:**

```
Class 'App\Models\User' not found
```

**Solusi:**

```bash
composer dump-autoload
```

### Error: 500 Internal Server Error

**Gejala:**
Halaman blank atau error 500

**Solusi:**

1. Cek PHP error log:

   ```bash
   # Linux
   tail -f /var/log/apache2/error.log

   # Atau aktifkan error display
   # Di index.php tambahkan:
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

2. Pastikan semua file memiliki permission yang benar:
   ```bash
   chmod -R 755 /var/www/goalmoney-api
   chown -R www-data:www-data /var/www/goalmoney-api
   ```

### Error: CORS Blocked

**Gejala:**

```
Access to XMLHttpRequest has been blocked by CORS policy
```

**Solusi:**
Edit `config/cors.php`:

```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
```

### Error: Token Invalid

**Gejala:**

```
401 Unauthorized
```

**Solusi:**

1. Pastikan format header benar:

   ```
   Authorization: Bearer eyJhbGci...
   ```

2. Cek apakah token expired di database

3. Login ulang untuk mendapat token baru

---

## 📌 Checklist Instalasi

- [ ] PHP 8.0+ terinstall
- [ ] PostgreSQL 12+ terinstall
- [ ] Composer 2.0+ terinstall
- [ ] Repository di-clone
- [ ] Dependencies terinstall (`composer install`)
- [ ] File `.env` dikonfigurasi
- [ ] Database dibuat
- [ ] Migrasi dijalankan
- [ ] Server berjalan
- [ ] Test API berhasil

---

**© 2024 GoalMoney API**
