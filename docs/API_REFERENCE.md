# API Reference - GoalMoney Backend

Dokumentasi lengkap semua API endpoints beserta contoh request dan response.

---

## 📋 Daftar Isi

1. [Authentication](#authentication)
2. [Profile](#profile)
3. [Goals](#goals)
4. [Transactions](#transactions)
5. [Withdrawals](#withdrawals)
6. [Dashboard](#dashboard)
7. [Reports](#reports)
8. [Badges](#badges)
9. [Analytics](#analytics)
10. [Recommendations](#recommendations)
11. [Notifications](#notifications)

---

## 🔐 Authentication

### POST `/auth/register`

Mendaftar akun baru.

**Request:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Success Response (201):**

```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "token": "abc123xyz...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "available_balance": 0,
      "created_at": "2024-01-15T10:30:00.000Z"
    }
  }
}
```

**Error Response (422):**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["Email already exists"]
  }
}
```

---

### POST `/auth/login`

Login ke akun.

**Request:**

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "abc123xyz...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "available_balance": 500000
    }
  }
}
```

---

### POST `/auth/logout`

Logout dari akun.

**Headers:**

```
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

## 👤 Profile

### GET `/profile`

Mendapatkan profil user.

**Headers:**

```
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "available_balance": 500000,
    "fcm_token": null,
    "created_at": "2024-01-15T10:30:00.000Z"
  }
}
```

---

### POST `/profile/update`

Update profil user.

**Headers:**

```
Authorization: Bearer {token}
```

**Request:**

```json
{
  "name": "John Updated",
  "current_password": "oldpassword",
  "new_password": "newpassword",
  "fcm_token": "firebase_token_here"
}
```

---

## 🎯 Goals

### GET `/goals`

Mendapatkan semua goals user.

**Headers:**

```
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Beli Laptop",
      "target_amount": 10000000,
      "current_amount": 3500000,
      "progress_percentage": 35.0,
      "deadline": "2024-12-31",
      "description": "MacBook Pro M3",
      "type": "digital",
      "is_completed": false,
      "created_at": "2024-01-15T10:30:00.000Z"
    }
  ]
}
```

---

### POST `/goals/store`

Membuat goal baru.

**Headers:**

```
Authorization: Bearer {token}
```

**Request:**

```json
{
  "name": "Beli Laptop",
  "target_amount": 10000000,
  "deadline": "2024-12-31",
  "description": "MacBook Pro M3",
  "type": "digital"
}
```

**Fields:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Nama goal (max 100) |
| target_amount | number | Yes | Target tabungan |
| deadline | date | No | Format YYYY-MM-DD |
| description | string | No | Deskripsi goal |
| type | string | No | 'digital' atau 'cash' |

---

### PUT `/goals/update?id={id}`

Update goal.

**Headers:**

```
Authorization: Bearer {token}
```

**Request:**

```json
{
  "name": "Beli Laptop Gaming",
  "target_amount": 15000000,
  "deadline": "2025-01-31"
}
```

---

### DELETE `/goals/delete?id={id}`

Hapus goal.

**Headers:**

```
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Goal deleted successfully",
  "data": {
    "refunded_amount": 3500000
  }
}
```

> **Note:** Jika goal memiliki saldo, saldo akan dikembalikan ke `available_balance` user.

---

## 💰 Transactions

### GET `/transactions?goal_id={id}`

Mendapatkan transaksi untuk goal tertentu.

**Headers:**

```
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "goal_id": 1,
      "amount": 500000,
      "method": "transfer",
      "description": "Gajian bulan ini",
      "transaction_date": "2024-01-20T14:30:00.000Z"
    }
  ]
}
```

---

### POST `/transactions/store`

Deposit ke goal.

**Headers:**

```
Authorization: Bearer {token}
```

**Request:**

```json
{
  "goal_id": 1,
  "amount": 500000,
  "method": "transfer",
  "description": "Gajian bulan ini"
}
```

**Fields:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| goal_id | integer | Yes | ID goal target |
| amount | number | Yes | Jumlah deposit |
| method | string | No | Metode: transfer/cash/ewallet |
| description | string | No | Catatan transaksi |

**Success Response (201):**

```json
{
  "success": true,
  "message": "Transaction successful",
  "data": {
    "transaction": { ... },
    "goal": { ... },
    "overflow": 0
  }
}
```

> **Note:** Jika deposit melebihi target, `overflow` akan berisi kelebihan yang masuk ke `available_balance`.

---

### POST `/transactions/allocate`

Alokasikan saldo dari `available_balance` ke goal.

**Request:**

```json
{
  "goal_id": 1,
  "amount": 250000
}
```

---

## 💸 Withdrawals

### GET `/withdrawals`

Mendapatkan history withdrawal.

**Headers:**

```
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "amount": 500000,
      "method": "dana",
      "account_number": "08123456789",
      "status": "pending",
      "notes": "Ambil sebagian",
      "created_at": "2024-01-25T16:00:00.000Z",
      "goal": null
    }
  ]
}
```

---

### POST `/withdrawals/request`

Request withdrawal dari available_balance atau goal.

**Request (dari available_balance):**

```json
{
  "amount": 500000,
  "method": "dana",
  "account_number": "08123456789",
  "notes": "Darurat"
}
```

**Request (dari goal tertentu):**

```json
{
  "goal_id": 1,
  "amount": 500000,
  "method": "gopay",
  "account_number": "08123456789"
}
```

**Methods:** `dana`, `gopay`, `ovo`, `bank_transfer`

---

### POST `/withdrawals/approve`

Approve/reject withdrawal request.

**Request:**

```json
{
  "withdrawal_id": 1,
  "status": "approved"
}
```

---

## 📊 Dashboard

### GET `/dashboard/summary`

Mendapatkan summary dashboard.

**Headers:**

```
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "total_saved": 5500000,
    "total_target": 15000000,
    "overall_progress": 36.67,
    "active_goals": 3,
    "completed_goals": 1,
    "total_goals": 4,
    "available_balance": 250000,
    "recent_transactions": [...]
  }
}
```

---

## 📈 Reports

### GET `/reports/report`

Mendapatkan laporan lengkap.

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| start_date | date | Tanggal mulai (YYYY-MM-DD) |
| end_date | date | Tanggal akhir |
| goal_id | integer | Filter by goal (optional) |

**Example:**

```
GET /reports/report?start_date=2024-01-01&end_date=2024-01-31
```

---

## 🏆 Badges

### GET `/badges`

Mendapatkan semua badges dan status earned.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "badges": [
      {
        "id": 1,
        "code": "first_saver",
        "name": "First Saver",
        "description": "Selamat! Kamu telah melakukan deposit pertamamu.",
        "icon": "🌟",
        "requirement_type": "first_deposit",
        "requirement_value": 1,
        "earned": true,
        "earned_at": "2024-01-15T10:35:00.000Z"
      }
    ],
    "stats": {
      "earned_count": 5,
      "total_count": 16,
      "progress": 31.25
    }
  }
}
```

---

### POST `/badges/check`

Check dan award badges baru.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "new_badges": [
      {
        "id": 3,
        "code": "week_warrior",
        "name": "Week Warrior",
        "icon": "🔥"
      }
    ],
    "checked": true
  }
}
```

---

## 📊 Analytics

### GET `/analytics/summary?year={year}`

Mendapatkan analytics summary.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "year": 2024,
    "monthly_trend": [
      { "month": 1, "month_name": "Januari", "total": 1500000, "count": 5 },
      { "month": 2, "month_name": "Februari", "total": 2000000, "count": 7 }
    ],
    "category_distribution": [
      { "label": "< 500rb", "count": 2, "total_saved": 750000 },
      { "label": "500rb - 2jt", "count": 1, "total_saved": 1200000 }
    ],
    "goal_comparison": [
      { "name": "Beli Laptop", "progress": 75.0, "target": 10000000 }
    ],
    "summary": {
      "total_saved": 5500000,
      "total_target": 15000000,
      "overall_progress": 36.67,
      "avg_monthly_saving": 458333
    }
  }
}
```

---

### GET `/analytics/streak?year={year}&month={month}`

Mendapatkan streak calendar data.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "year": 2024,
    "calendar": {
      "2024-01-15": { "amount": 500000, "count": 1, "intensity": 3 },
      "2024-01-16": { "amount": 100000, "count": 1, "intensity": 1 }
    },
    "streak": {
      "current": 7,
      "longest": 14,
      "last_deposit": "2024-01-25",
      "is_active_today": true
    },
    "monthly_summary": [...]
  }
}
```

---

## 🤖 Recommendations

### GET `/recommendations`

Mendapatkan smart savings recommendations.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "recommendations": [
      {
        "goal_id": 1,
        "goal_name": "Beli Laptop",
        "remaining": 6500000,
        "progress": 35.0,
        "days_remaining": 90,
        "daily_suggestion": 72222,
        "weekly_suggestion": 505556,
        "urgency": "medium",
        "status": "on_track",
        "tip": "⭐ Bagus! Sudah setengah jalan. Terus konsisten menabung."
      }
    ],
    "count": 3,
    "global_tip": "💰 Terus konsisten menabung!"
  }
}
```

**Urgency Levels:**
| Level | Days Remaining |
|-------|---------------|
| critical | < 7 days |
| high | < 30 days |
| medium | < 90 days |
| normal | ≥ 90 days |

---

## 🔔 Notifications

### GET `/notifications`

Mendapatkan notifikasi user.

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "badge_earned",
      "title": "Badge Baru!",
      "message": "Kamu mendapatkan badge Week Warrior 🔥",
      "read": false,
      "created_at": "2024-01-25T10:30:00.000Z"
    }
  ]
}
```

---

## ⚠️ Error Codes

| Code | Description                              |
| ---- | ---------------------------------------- |
| 400  | Bad Request - Invalid parameters         |
| 401  | Unauthorized - Invalid/missing token     |
| 403  | Forbidden - Access denied                |
| 404  | Not Found - Resource not found           |
| 422  | Unprocessable Entity - Validation failed |
| 500  | Internal Server Error                    |

---

**© 2024 GoalMoney API**
