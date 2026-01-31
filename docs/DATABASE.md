# Database Schema - GoalMoney

Dokumentasi lengkap schema database PostgreSQL untuk GoalMoney.

---

## 📋 Daftar Tabel

1. [users](#users)
2. [tokens](#tokens)
3. [goals](#goals)
4. [transactions](#transactions)
5. [withdrawals](#withdrawals)
6. [badges](#badges)
7. [user_badges](#user_badges)
8. [notifications](#notifications)

---

## Entity Relationship Diagram

```
┌─────────────┐       ┌─────────────┐       ┌─────────────────┐
│   users     │───────│   goals     │───────│  transactions   │
└─────────────┘       └─────────────┘       └─────────────────┘
      │                     │
      │                     │
      ▼                     ▼
┌─────────────┐       ┌─────────────┐
│   tokens    │       │ withdrawals │
└─────────────┘       └─────────────┘

┌─────────────┐       ┌─────────────┐
│   badges    │───────│ user_badges │──────────────┐
└─────────────┘       └─────────────┘              │
                                                   ▼
                                            ┌─────────────┐
                                            │   users     │
                                            └─────────────┘
```

---

## Tabel: `users`

Menyimpan data user.

| Kolom             | Tipe          | Constraint       | Default | Deskripsi                   |
| ----------------- | ------------- | ---------------- | ------- | --------------------------- |
| id                | SERIAL        | PRIMARY KEY      | auto    | ID unik                     |
| name              | VARCHAR(100)  | NOT NULL         | -       | Nama lengkap                |
| email             | VARCHAR(100)  | UNIQUE, NOT NULL | -       | Email login                 |
| password          | VARCHAR(255)  | NOT NULL         | -       | Password (bcrypt)           |
| available_balance | DECIMAL(15,2) | NOT NULL         | 0.00    | Saldo tersedia              |
| fcm_token         | VARCHAR(255)  | -                | NULL    | Token FCM push notification |
| created_at        | TIMESTAMP     | NOT NULL         | NOW()   | Waktu registrasi            |
| updated_at        | TIMESTAMP     | NOT NULL         | NOW()   | Waktu update terakhir       |

**Indexes:**

- `PRIMARY KEY (id)`
- `UNIQUE INDEX (email)`

**SQL:**

```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    available_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    fcm_token VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## Tabel: `tokens`

Menyimpan token autentikasi.

| Kolom      | Tipe         | Constraint            | Default | Deskripsi          |
| ---------- | ------------ | --------------------- | ------- | ------------------ |
| id         | SERIAL       | PRIMARY KEY           | auto    | ID unik            |
| user_id    | INT          | FOREIGN KEY, NOT NULL | -       | Referensi ke users |
| token      | VARCHAR(255) | UNIQUE, NOT NULL      | -       | Token string       |
| expires_at | TIMESTAMP    | NOT NULL              | -       | Waktu expired      |
| created_at | TIMESTAMP    | NOT NULL              | NOW()   | Waktu dibuat       |

**Relations:**

- `user_id` → `users(id)` ON DELETE CASCADE

**SQL:**

```sql
CREATE TABLE tokens (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## Tabel: `goals`

Menyimpan goals tabungan user.

| Kolom          | Tipe          | Constraint            | Default   | Deskripsi          |
| -------------- | ------------- | --------------------- | --------- | ------------------ |
| id             | SERIAL        | PRIMARY KEY           | auto      | ID unik            |
| user_id        | INT           | FOREIGN KEY, NOT NULL | -         | Referensi ke users |
| name           | VARCHAR(100)  | NOT NULL              | -         | Nama goal          |
| target_amount  | DECIMAL(15,2) | NOT NULL              | -         | Target tabungan    |
| current_amount | DECIMAL(15,2) | NOT NULL              | 0.00      | Tabungan saat ini  |
| deadline       | DATE          | -                     | NULL      | Tanggal deadline   |
| description    | TEXT          | -                     | NULL      | Deskripsi goal     |
| type           | VARCHAR(20)   | NOT NULL              | 'digital' | Tipe: digital/cash |
| created_at     | TIMESTAMP     | NOT NULL              | NOW()     | Waktu dibuat       |
| updated_at     | TIMESTAMP     | NOT NULL              | NOW()     | Waktu update       |

**Relations:**

- `user_id` → `users(id)` ON DELETE CASCADE

**Computed Fields (via Model):**

- `progress_percentage` = (current_amount / target_amount) × 100
- `is_completed` = current_amount >= target_amount

**SQL:**

```sql
CREATE TABLE goals (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    deadline DATE,
    description TEXT,
    type VARCHAR(20) NOT NULL DEFAULT 'digital',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## Tabel: `transactions`

Menyimpan transaksi deposit.

| Kolom            | Tipe          | Constraint            | Default | Deskripsi                     |
| ---------------- | ------------- | --------------------- | ------- | ----------------------------- |
| id               | SERIAL        | PRIMARY KEY           | auto    | ID unik                       |
| goal_id          | INT           | FOREIGN KEY, NOT NULL | -       | Referensi ke goals            |
| amount           | DECIMAL(15,2) | NOT NULL              | -       | Jumlah deposit                |
| method           | VARCHAR(50)   | -                     | NULL    | Metode: transfer/cash/ewallet |
| description      | TEXT          | -                     | NULL    | Catatan transaksi             |
| transaction_date | TIMESTAMP     | NOT NULL              | NOW()   | Waktu transaksi               |
| created_at       | TIMESTAMP     | NOT NULL              | NOW()   | Waktu dibuat                  |

**Relations:**

- `goal_id` → `goals(id)` ON DELETE CASCADE

**SQL:**

```sql
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    goal_id INT NOT NULL REFERENCES goals(id) ON DELETE CASCADE,
    amount DECIMAL(15,2) NOT NULL,
    method VARCHAR(50),
    description TEXT,
    transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## Tabel: `withdrawals`

Menyimpan request penarikan.

| Kolom          | Tipe          | Constraint            | Default   | Deskripsi                 |
| -------------- | ------------- | --------------------- | --------- | ------------------------- |
| id             | SERIAL        | PRIMARY KEY           | auto      | ID unik                   |
| user_id        | INT           | FOREIGN KEY, NOT NULL | -         | Referensi ke users        |
| goal_id        | INT           | FOREIGN KEY           | NULL      | Goal sumber (optional)    |
| amount         | DECIMAL(15,2) | NOT NULL              | -         | Jumlah penarikan          |
| method         | VARCHAR(50)   | NOT NULL              | -         | dana/gopay/ovo/bank       |
| account_number | VARCHAR(50)   | NOT NULL              | -         | Nomor akun/telepon        |
| status         | VARCHAR(20)   | NOT NULL              | 'pending' | pending/approved/rejected |
| notes          | TEXT          | -                     | NULL      | Catatan                   |
| approved_at    | TIMESTAMP     | -                     | NULL      | Waktu approve             |
| created_at     | TIMESTAMP     | NOT NULL              | NOW()     | Waktu request             |
| updated_at     | TIMESTAMP     | NOT NULL              | NOW()     | Waktu update              |

**Relations:**

- `user_id` → `users(id)` ON DELETE CASCADE
- `goal_id` → `goals(id)` ON DELETE SET NULL

**Status Values:**
| Status | Deskripsi |
|--------|-----------|
| pending | Menunggu approval |
| approved | Disetujui |
| rejected | Ditolak |

**SQL:**

```sql
CREATE TABLE withdrawals (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    goal_id INT REFERENCES goals(id) ON DELETE SET NULL,
    amount DECIMAL(15,2) NOT NULL,
    method VARCHAR(50) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    notes TEXT,
    approved_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## Tabel: `badges`

Menyimpan master data badges.

| Kolom             | Tipe         | Constraint       | Default | Deskripsi         |
| ----------------- | ------------ | ---------------- | ------- | ----------------- |
| id                | SERIAL       | PRIMARY KEY      | auto    | ID unik           |
| code              | VARCHAR(50)  | UNIQUE, NOT NULL | -       | Kode unik badge   |
| name              | VARCHAR(100) | NOT NULL         | -       | Nama display      |
| description       | TEXT         | NOT NULL         | -       | Deskripsi badge   |
| icon              | VARCHAR(10)  | NOT NULL         | -       | Emoji icon        |
| requirement_type  | VARCHAR(50)  | NOT NULL         | -       | Tipe requirement  |
| requirement_value | INT          | NOT NULL         | -       | Nilai requirement |
| created_at        | TIMESTAMP    | NOT NULL         | NOW()   | Waktu dibuat      |

**Requirement Types:**
| Type | Deskripsi |
|------|-----------|
| first_deposit | Deposit pertama |
| streak | Streak X hari berturut-turut |
| goal_complete | Selesaikan X goal |
| total_saved | Total tabungan >= X |
| active_goals | Punya X goal aktif |
| deposit_count | Deposit sebanyak X kali |
| early_complete | Selesai sebelum deadline X kali |

**SQL:**

```sql
CREATE TABLE badges (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(10) NOT NULL,
    requirement_type VARCHAR(50) NOT NULL,
    requirement_value INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## Tabel: `user_badges`

Menyimpan badges yang dimiliki user (pivot table).

| Kolom     | Tipe      | Constraint            | Default | Deskripsi           |
| --------- | --------- | --------------------- | ------- | ------------------- |
| id        | SERIAL    | PRIMARY KEY           | auto    | ID unik             |
| user_id   | INT       | FOREIGN KEY, NOT NULL | -       | Referensi ke users  |
| badge_id  | INT       | FOREIGN KEY, NOT NULL | -       | Referensi ke badges |
| earned_at | TIMESTAMP | NOT NULL              | NOW()   | Waktu earned        |

**Relations:**

- `user_id` → `users(id)` ON DELETE CASCADE
- `badge_id` → `badges(id)` ON DELETE CASCADE

**Unique Constraint:**

- `UNIQUE (user_id, badge_id)` - User hanya bisa dapat 1 badge per jenis

**SQL:**

```sql
CREATE TABLE user_badges (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    badge_id INT NOT NULL REFERENCES badges(id) ON DELETE CASCADE,
    earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, badge_id)
);
```

---

## Tabel: `notifications`

Menyimpan notifikasi untuk user.

| Kolom      | Tipe         | Constraint            | Default | Deskripsi          |
| ---------- | ------------ | --------------------- | ------- | ------------------ |
| id         | SERIAL       | PRIMARY KEY           | auto    | ID unik            |
| user_id    | INT          | FOREIGN KEY, NOT NULL | -       | Referensi ke users |
| type       | VARCHAR(50)  | NOT NULL              | -       | Tipe notifikasi    |
| title      | VARCHAR(255) | NOT NULL              | -       | Judul              |
| message    | TEXT         | NOT NULL              | -       | Isi pesan          |
| read       | BOOLEAN      | NOT NULL              | FALSE   | Status baca        |
| created_at | TIMESTAMP    | NOT NULL              | NOW()   | Waktu dibuat       |

**Notification Types:**
| Type | Deskripsi |
|------|-----------|
| badge_earned | Badge baru didapat |
| goal_completed | Goal selesai |
| goal_reminder | Reminder goal |
| withdrawal_approved | Withdrawal disetujui |
| system | Notifikasi sistem |

**SQL:**

```sql
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔗 Seeded Data

### Badges (16 records)

| Code               | Name               | Icon | Type           | Value   |
| ------------------ | ------------------ | ---- | -------------- | ------- |
| first_saver        | First Saver        | 🌟   | first_deposit  | 1       |
| getting_started    | Getting Started    | ⚡   | streak         | 3       |
| week_warrior       | Week Warrior       | 🔥   | streak         | 7       |
| fortnight_fighter  | Fortnight Fighter  | 💪   | streak         | 14      |
| monthly_master     | Monthly Master     | 💎   | streak         | 30      |
| goal_achiever      | Goal Achiever      | 🏆   | goal_complete  | 1       |
| triple_victory     | Triple Victory     | 🎖️   | goal_complete  | 3       |
| goal_master        | Goal Master        | 👑   | goal_complete  | 5       |
| hundred_thousander | Hundred Thousander | 💵   | total_saved    | 100000  |
| half_millionaire   | Half Millionaire   | 💴   | total_saved    | 500000  |
| millionaire        | Millionaire        | 💰   | total_saved    | 1000000 |
| multi_millionaire  | Multi Millionaire  | 💎   | total_saved    | 5000000 |
| multi_tasker       | Multi Tasker       | 🎯   | active_goals   | 3       |
| regular_saver      | Regular Saver      | 📊   | deposit_count  | 10      |
| super_saver        | Super Saver        | 📈   | deposit_count  | 50      |
| early_bird         | Early Bird         | 🐦   | early_complete | 1       |

---

## 📊 Indexes Recommendations

Untuk performa optimal, tambahkan indexes berikut:

```sql
-- Goals indexes
CREATE INDEX idx_goals_user_id ON goals(user_id);
CREATE INDEX idx_goals_deadline ON goals(deadline);

-- Transactions indexes
CREATE INDEX idx_transactions_goal_id ON transactions(goal_id);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);

-- Withdrawals indexes
CREATE INDEX idx_withdrawals_user_id ON withdrawals(user_id);
CREATE INDEX idx_withdrawals_status ON withdrawals(status);

-- User badges indexes
CREATE INDEX idx_user_badges_user_id ON user_badges(user_id);

-- Notifications indexes
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(read);
```

---

**© 2024 GoalMoney Database**
