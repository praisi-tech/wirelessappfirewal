# Railway Deployment Guide untuk Crypto WAF

## Langkah-Langkah Deployment

### 1. Push ke GitHub
```bash
git add .
git commit -m "Prepare for Railway deployment"
git push origin main
```

### 2. Di Railway Dashboard

#### a) Buat Project Baru
- Masuk ke [railway.app](https://railway.app)
- Klik "New Project"
- Pilih "Import from GitHub"
- Pilih repository `crypto-waf`

#### b) Tambah MySQL Database
1. Klik "+ Add Service"
2. Pilih "MySQL"
3. Railway akan otomatis generate:
   - `Mysql.MYSQL_HOST`
   - `Mysql.MYSQL_PORT`
   - `Mysql.MYSQL_USER`
   - `Mysql.MYSQL_PASSWORD`
   - `Mysql.MYSQL_DATABASE`
   - `Mysql.DATABASE_URL` (format: `mysql://user:pass@host:port/db`)

#### c) Set Environment Variables di Railway

Masuk ke Project → Variables → Tambahkan:

```
APP_NAME=CryptoWAF
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.up.railway.app
APP_KEY=base64:Nq6swHHarF1e/ty9cBRT8B+kT6UYtScKnNUOISYL3TY=

# Database (Railway akan inject otomatis dari MySQL service)
# DATABASE_URL=${{Mysql.DATABASE_URL}}
# DB_HOST=${{Mysql.MYSQL_HOST}}
# DB_PORT=${{Mysql.MYSQL_PORT}}
# DB_DATABASE=${{Mysql.MYSQL_DATABASE}}
# DB_USERNAME=${{Mysql.MYSQL_USER}}
# DB_PASSWORD=${{Mysql.MYSQL_PASSWORD}}

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Crypto WAF Settings
CRYPTO_ALGORITHM=aes-256-gcm
CRYPTO_KEY=base64:A3J946iw7o8Xvm/IWNMjhzIuS2EGlUlmv91IRlutQPQ=
HMAC_KEY=mqKsyFFJVlR6IiHjQjvSUpk92v53gFgr5lt1qgMTsDLQ22bSTwhsrGzPQ5twE7Aa
SIGNATURE_ALGORITHM=sha256
TOKEN_EXPIRY=3600

WAF_ENABLED=true
WAF_RATE_LIMIT=60
WAF_MAX_LOGIN_ATTEMPTS=5
WAF_BLOCK_DURATION=900
WAF_LOG_STORAGE_DAYS=30

SECURITY_HEADERS_ENABLED=true
HSTS_ENABLED=true
CSP_ENABLED=false

DEBUGBAR_ENABLED=false
```

### 3. Buat Procfile (di root directory)

```
web: vendor/bin/heroku-php-apache2 public/
release: php artisan migrate --force && php artisan cache:clear
```

### 4. Verifikasi database.php

✅ **database.php sudah di-update** untuk support:
- `DATABASE_URL` dari Railway (prioritas tinggi)
- `DB_HOST`, `DB_PORT`, dll sebagai fallback

### 5. Deploy

1. Commit dan push Procfile:
```bash
git add Procfile
git commit -m "Add Procfile for Railway"
git push origin main
```

2. Railway akan:
   - Otomatis detect PHP + Laravel
   - Install dependencies (`composer install`)
   - Run migration (`php artisan migrate`)
   - Deploy aplikasi

### 6. Cek Logs
Di Railway Dashboard → Project → Deployments → View Logs

---

## Troubleshooting

### Error: "Lost connection to MySQL"
**Solusi:** Railway MySQL memiliki timeout 30s untuk idle connections. Tambah di `.env`:
```
DB_CONNECTION_FACTORY_DISABLE_CONSTRAINTS=true
```

### Error: "Unknown collation 'utf8mb4_0900_ai_ci'"
**Solusi:** Railway MySQL versi lama. Ubah `DB_COLLATION` di `.env`:
```
DB_COLLATION=utf8mb4_unicode_ci
```

### APP_KEY tidak ter-generate
**Solusi:** Gunakan APP_KEY yang sama dengan lokal:
```
APP_KEY=base64:Nq6swHHarF1e/ty9cBRT8B+kT6UYtScKnNUOISYL3TY=
```

---

## Environment Variables Yang Di-Inject Railway

Ketika MySQL service di-connect, Railway otomatis inject:

```
Mysql.DATABASE_URL    → mysql://user:pass@host:port/dbname
Mysql.MYSQL_HOST      → host
Mysql.MYSQL_PORT      → 3306
Mysql.MYSQL_USER      → username
Mysql.MYSQL_PASSWORD  → password
Mysql.MYSQL_DATABASE  → database_name
```

**Penting:** `DATABASE_URL` adalah format standar yang Laravel sudah support. Gunakan yang ini daripada individual variables!

---

## Notes

- Railway free tier: $5/month credits
- MySQL database gratis selama dalam satu project
- Auto-scaling & monitoring included
- Custom domain support (berbayar atau free .up.railway.app)
