# Railway Deployment Guide untuk Crypto WAF

## ⚡ Quick Start (Yang PALING PENTING)

### 1. Push ke GitHub
```bash
git add .
git commit -m "Setup Railway deployment"
git push origin main
```

### 2. Create Project di Railway
1. Masuk ke https://railway.app
2. Buat "New Project" → "Import from GitHub"
3. Pilih repository `crypto-waf`

### 3. **PENTING: Add MySQL Service**
1. Di Railway Dashboard project, klik **"+ Add Service"**
2. Pilih **"MySQL"**
3. Railway akan otomatis membuat instance MySQL
4. **TUNGGU sampai status = "Running"** (bisa 2-3 menit)

### 4. **PENTING: Link MySQL ke App Variables**

Railway memiliki 2 panel:

#### Panel Kiri: Project Services
- Lihat MySQL service yang sudah di-create
- Lihat PHP app service

#### Panel Kanan: Variables (APA YANG KAMU LAKUKAN)

**A) Buka MySQL service → Variables**
- Copy semua variables yang ada (READ-ONLY, jangan diedit)
- Lihat `DATABASE_URL`, `MYSQL_HOST`, `MYSQL_USER`, dll

**B) Buka PHP App service → Variables**
- Tambahkan variables untuk koneksi database

---

## 🔧 Setup Variables di Railway APP Service

Di Railway Dashboard → Project → Pilih "crypto-waf" service → Variables

### Option A: Gunakan DATABASE_URL (RECOMMENDED)

Jika Railway MySQL service memberi `DATABASE_URL` (format: `mysql://user:pass@host:port/db`), gunakan ini:

```
DATABASE_URL=${{Mysql.DATABASE_URL}}
```

Kemudian tambahan lainnya:
```
APP_NAME=CryptoWAF
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:Nq6swHHarF1e/ty9cBRT8B+kT6UYtScKnNUOISYL3TY=
APP_URL=https://your-custom-domain.com

# Database - PRIMARY METHOD
DATABASE_URL=${{Mysql.DATABASE_URL}}

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Crypto WAF
CRYPTO_ALGORITHM=aes-256-gcm
CRYPTO_KEY=base64:A3J946iw7o8Xvm/IWNMjhzIuS2EGlUlmv91IRlutQPQ=
HMAC_KEY=mqKsyFFJVlR6IiHjQjvSUpk92v53gFgr5lt1qgMTsDLQ22bSTwhsrGzPQ5twE7Aa
SIGNATURE_ALGORITHM=sha256
TOKEN_EXPIRY=3600
WAF_ENABLED=true
WAF_RATE_LIMIT=60
WAF_MAX_LOGIN_ATTEMPTS=5
WAF_BLOCK_DURATION=900
DEBUGBAR_ENABLED=false
```

### Option B: Manual Individual Variables

Jika DATABASE_URL tidak tersedia, gunakan individual vars:

```
APP_NAME=CryptoWAF
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:Nq6swHHarF1e/ty9cBRT8B+kT6UYtScKnNUOISYL3TY=
APP_URL=https://your-custom-domain.com

# Database - MANUAL METHOD
DB_CONNECTION=mysql
DB_HOST=${{Mysql.MYSQL_HOST}}
DB_PORT=${{Mysql.MYSQL_PORT}}
DB_DATABASE=${{Mysql.MYSQL_DATABASE}}
DB_USERNAME=${{Mysql.MYSQL_USER}}
DB_PASSWORD=${{Mysql.MYSQL_PASSWORD}}

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# WAF Settings
CRYPTO_ALGORITHM=aes-256-gcm
CRYPTO_KEY=base64:A3J946iw7o8Xvm/IWNMjhzIuS2EGlUlmv91IRlutQPQ=
HMAC_KEY=mqKsyFFJVlR6IiHjQjvSUpk92v53gFgr5lt1qgMTsDLQ22bSTwhsrGzPQ5twE7Aa
SIGNATURE_ALGORITHM=sha256
TOKEN_EXPIRY=3600
WAF_ENABLED=true
WAF_RATE_LIMIT=60
WAF_MAX_LOGIN_ATTEMPTS=5
WAF_BLOCK_DURATION=900
DEBUGBAR_ENABLED=false
```

---

## ✅ Verification Checklist

1. **MySQL Service**: Status "Running" ✓
2. **Variables Set**: Semua variables ada di PHP app ✓
3. **Procfile exists**: File ada di root directory ✓
4. **railway-setup.sh exists**: File ada di root directory ✓

---

## 🚀 Deploy

1. Pastikan semua file sudah di-commit:
```bash
git status
git add .
git commit -m "Railway deployment ready"
git push origin main
```

2. Railway akan otomatis deploy saat mendeteksi push
3. Cek deployment progress di Railway Dashboard

---

## 📊 Cek Status Deployment

1. Railway Dashboard → Project → Deployments
2. Lihat logs saat deployment running
3. Cari pesan: "Migrations completed successfully" atau error message

---

## 🔍 Troubleshooting

### ❌ Error: "Host: localhost, Port: 3306"
**Penyebab**: Variables tidak ter-set di Railway
**Solusi**: 
1. Buka Railway App service → Variables
2. Pastikan DATABASE_URL atau DB_HOST sudah ada
3. Re-deploy dengan tombol "Deploy" atau push baru ke GitHub

### ❌ Error: "No such file or directory"
**Penyebab**: Database belum siap saat migration
**Solusi**:
1. Tunggu MySQL service status = "Running"
2. Tunggu 2-3 menit lagi
3. Trigger manual deploy: Railway Dashboard → Deployments → "Redeploy"

### ❌ Blank Page atau 500 Error
**Solusi**:
1. Klik "View Logs" di Railway Deployments
2. Cari error message spesifik
3. Baca RAILWAY_DEPLOYMENT.md section Logs

---

## 📝 Environment Variables Yang Di-Inject Railway

MySQL service otomatis generate (READ dari MySQL service panel):
```
DATABASE_URL=mysql://user:pass@host:port/dbname
MYSQL_HOST=host
MYSQL_PORT=3306
MYSQL_USER=user
MYSQL_PASSWORD=pass
MYSQL_DATABASE=cryptowaf
```

**PENTING**: Gunakan `${{Mysql.VARIABLE_NAME}}` syntax saat referencing dari service lain!

---

## 🌐 Custom Domain Setup (Optional)

1. Railway Dashboard → Project Settings → Domain
2. Bisa: Pakai `.up.railway.app` gratis (setup auto)
3. Atau: Custom domain (berbayar atau free)
4. Update `APP_URL` di Variables ke domain kamu

---

## 📚 Useful Railway Commands

Monitor logs:
```bash
railway logs -f
```

SSH ke dyno:
```bash
railway shell
```

Manual migration (jika diperlukan):
```bash
railway run php artisan migrate:fresh --seed
```

---

## ⚠️ Important Notes

- **First Deploy**: Bisa gagal migration jika MySQL belum siap. Tunggu 2-3 menit, lalu redeploy
- **Free Tier**: Railway kasih $5/bulan kredit. MySQL included
- **Auto-Restart**: Jika app crash, Railway otomatis restart
- **Logs**: Selalu check logs di Railway Dashboard jika ada error

