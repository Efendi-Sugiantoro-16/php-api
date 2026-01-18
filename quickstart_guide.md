# Quick Start Guide - GoalMoney API ORM

Panduan cepat untuk menjalankan GoalMoney API dengan Eloquent ORM.

## 🚀 Setup dalam 5 Menit

### 1. Install Dependencies

```bash
composer install
```

### 2. Setup Environment

```bash
# Copy file .env
cp .env.example .env

# Edit konfigurasi database
nano .env
```

Minimal konfigurasi di `.env`:
```env
DB_DATABASE=goalmoney_db
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 3. Create Database

```bash
# Masuk ke PostgreSQL
psql -U postgres

# Buat database
CREATE DATABASE goalmoney_db;
\q
```

### 4. Run Migration

**Pilihan A: Menggunakan SQL file**
```bash
psql -U postgres -d goalmoney_db -f database_schema.sql
```

**Pilihan B: Menggunakan migration script**
```bash
php migrations/create_tables.php
```

### 5. (Opsional) Seed Data untuk Testing

```bash
php seeders/seed_data.php
```

### 6. Test API

```bash
# Test endpoint welcome
curl http://localhost/goalmoney-api/

# Test register
curl -X POST http://localhost/goalmoney-api/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

## 📁 Struktur Project

```
goalmoney-api/
├── api/                    # API endpoints
│   ├── auth/              # Authentication
│   ├── goals/             # Goals CRUD
│   ├── transactions/      # Transactions CRUD
│   ├── profile/           # User profile
│   └── dashboard/         # Dashboard summary
├── app/                   # Application logic
│   ├── models/           # Eloquent models
│   ├── middleware/       # Middleware (auth)
│   └── helpers/          # Helper functions
├── config/               # Configuration files
├── migrations/           # Database migrations
├── seeders/             # Data seeders
├── vendor/              # Composer dependencies
├── .env                 # Environment variables (create this)
├── .env.example         # Environment template
├── bootstrap.php        # Application bootstrap
├── composer.json        # Composer config
└── index.php           # Entry point
```

## 🔑 Test Account (setelah seeding)

```
Email: test@goalmoney.com
Password: password123
```

## 📝 Common Commands

### Check Database Connection
```bash
php -r "require 'bootstrap.php'; echo 'Database connected!';"
```

### Regenerate Autoload
```bash
composer dump-autoload
```

### Clear Composer Cache
```bash
composer clear-cache
```

## 🐛 Troubleshooting

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: "Connection refused"
```bash
# Check PostgreSQL is running
sudo service postgresql status

# Check .env configuration
cat .env | grep DB_
```

### Error: "Table doesn't exist"
```bash
# Run migrations
php migrations/create_tables.php
```

## 📚 Next Steps

1. ✅ Baca `README.md` untuk dokumentasi lengkap
2. ✅ Test semua endpoints dengan Postman/Thunder Client
3. ✅ Mulai develop Flutter app
4. ✅ Integrasikan dengan backend API

## 🎯 API Endpoints Overview

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/auth/register | ❌ | Register user baru |
| POST | /api/auth/login | ❌ | Login user |
| GET | /api/profile/user | ✅ | Get user profile |
| GET | /api/dashboard/summary | ✅ | Get dashboard summary |
| GET | /api/goals/index | ✅ | List semua goals |
| POST | /api/goals/store | ✅ | Create goal baru |
| PUT | /api/goals/update | ✅ | Update goal |
| DELETE | /api/goals/delete | ✅ | Delete goal |
| GET | /api/transactions/index | ✅ | List transactions by goal |
| POST | /api/transactions/store | ✅ | Create transaction |
| DELETE | /api/transactions/delete | ✅ | Delete transaction |

✅ = Requires Bearer Token

## 💡 Tips

1. **Gunakan Postman Collection**: Import dari folder `postman/` jika ada
2. **Enable Debug Mode**: Set `APP_DEBUG=true` di `.env` saat development
3. **Monitor Logs**: Check error_log untuk debugging
4. **Use Migration Script**: Lebih mudah daripada SQL manual
5. **Seed Data**: Gunakan untuk testing tanpa manual input

## 🔐 Security Checklist

- [x] Password di-hash dengan bcrypt
- [x] Token authentication untuk protected endpoints
- [x] Environment variables untuk konfigurasi sensitif
- [x] Eloquent ORM mencegah SQL injection
- [x] CORS configured
- [x] Input validation di semua endpoint

Happy Coding! 🚀
