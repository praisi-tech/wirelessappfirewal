# Railway Deployment Checklist

Gunakan checklist ini sebelum deploy ke Railway untuk memastikan semuanya siap.

## 📋 Pre-Deployment Checklist

### Local Setup
- [ ] Sudah push semua perubahan ke GitHub
  ```bash
  git status  # harus clean
  git push origin main
  ```

### Files Required
- [ ] `Procfile` ada di root directory
- [ ] `railway-setup.sh` ada di root directory
- [ ] `config/database.php` sudah updated
- [ ] `.env.railway` ada sebagai reference

### GitHub
- [ ] Repository sudah public atau authorized untuk Railway
- [ ] Main/master branch adalah versi terbaru

---

## 🚀 Railway Deployment Steps (IN ORDER!)

### Step 1: Create Project
- [ ] Login ke https://railway.app
- [ ] Click "New Project"
- [ ] "Import GitHub Repo"
- [ ] Select `crypto-waf` repository
- [ ] Click "Create"

### Step 2: Add MySQL Service
- [ ] Click "+ Add Service"
- [ ] Select "MySQL"
- [ ] Click "Create"
- [ ] **TUNGGU sampai status = "Running"** (2-3 menit)

### Step 3: Link Database Variables (CRITICAL!)
- [ ] Click "MySQL" service
- [ ] Go to "Variables" tab
- [ ] **Copy semua variables** (untuk referensi)
- [ ] Click "crypto-waf" (PHP app service)
- [ ] Go to "Variables" tab

### Step 4: Add App Variables
Di PHP app Variables section, **pilih salah satu method**:

#### Method A: DATABASE_URL (RECOMMENDED)
- [ ] Click "Add Variable"
- [ ] Name: `DATABASE_URL`
- [ ] Value: `${{Mysql.DATABASE_URL}}`
- [ ] Save
- [ ] (Optional: Add other vars dari .env.railway)

#### Method B: Individual MYSQL Variables
- [ ] Click "Add Variable" for each:
  - [ ] `DB_CONNECTION` = `mysql`
  - [ ] `DB_HOST` = `${{Mysql.MYSQL_HOST}}`
  - [ ] `DB_PORT` = `${{Mysql.MYSQL_PORT}}`
  - [ ] `DB_DATABASE` = `${{Mysql.MYSQL_DATABASE}}`
  - [ ] `DB_USERNAME` = `${{Mysql.MYSQL_USER}}`
  - [ ] `DB_PASSWORD` = `${{Mysql.MYSQL_PASSWORD}}`

### Step 5: Add Essential Variables
- [ ] `APP_NAME` = `CryptoWAF`
- [ ] `APP_ENV` = `production`
- [ ] `APP_DEBUG` = `false`
- [ ] `APP_KEY` = `base64:Nq6swHHarF1e/ty9cBRT8B+kT6UYtScKnNUOISYL3TY=`
- [ ] `APP_URL` = `https://your-custom-domain.com` (atau .up.railway.app)

### Step 6: Add Security Variables
- [ ] `CRYPTO_KEY` = `base64:A3J946iw7o8Xvm/IWNMjhzIuS2EGlUlmv91IRlutQPQ=`
- [ ] `HMAC_KEY` = `mqKsyFFJVlR6IiHjQjvSUpk92v53gFgr5lt1qgMTsDLQ22bSTwhsrGzPQ5twE7Aa`

### Step 7: Trigger Deployment
- [ ] Push baru ke GitHub (atau click "Redeploy"):
  ```bash
  git add .
  git commit -m "Railway deployment configured"
  git push origin main
  ```

### Step 8: Monitor Deployment
- [ ] Go to "Deployments" tab
- [ ] Tunggu status "Running" (atau "Crashed" jika error)
- [ ] Click "View Logs"
- [ ] Cari message:
  - ✅ `✓ Migrations completed successfully`
  - ✅ `✓ Setup completed!`

---

## ✅ Verification After Deployment

### Check App is Running
- [ ] Click deployed app → "Open Deployment"
- [ ] Lihat website (bukan 500 error)
- [ ] Homepage load successfully

### Check Logs for Errors
- [ ] Deployments tab → View Logs
- [ ] Cari "ERROR" atau "FAILED"
- [ ] Tidak ada error "localhost:3306"

### Test Database Connection
- [ ] Akses `/api` endpoint (jika ada)
- [ ] Atau: Lihat database di MySQL service panel
- [ ] Table `migrations` sudah ada

---

## 🔴 If Something Goes Wrong

### See Error in Logs
1. Deployments → View Logs
2. Cari exact error message
3. Check `DEBUG_LOCALHOST_ERROR.md` file

### Common Errors & Solutions

| Error | Solution |
|-------|----------|
| `Host: localhost, Port: 3306` | Variables tidak ter-set, baca Step 3-5 lagi |
| `No such file or directory` | MySQL belum siap, tunggu 3-5 menit lagi |
| `SQLSTATE[HY000]` | Connection error, check DB_* variables |
| Blank page / 500 | Check logs, bisa APP_KEY issue |

### Redeploy
- [ ] Click "Redeploy" button
- [ ] atau: Push commit baru ke GitHub
- [ ] Tunggu deployment selesai

---

## 🌐 Custom Domain (Optional)

Setelah deployment berhasil:

1. Railway Dashboard → Project Settings → Domain
2. Pilih opsi:
   - **Railway Subdomain** (gratis, auto): `yourapp.up.railway.app`
   - **Custom Domain** (perlu DNS config): `yourdomain.com`
3. Update `APP_URL` variable jika ganti domain

---

## 📚 Important Notes

| Item | Detail |
|------|--------|
| First Deploy | Bisa gagal, tunggu 3-5 menit, redeploy |
| Migration Errors | Normal jika DB belum siap, skip dengan `|| true` |
| Free Tier | Gratis PHP + MySQL dengan $5/bulan credit |
| Auto Restart | App auto-restart jika crash |
| Logs | Always check logs jika error |
| Config Cache | Disable di release phase agar baca fresh env vars |

---

## 🎯 Success Indicators

Deployment **BERHASIL** jika:
1. ✅ Status "Running" di Deployments
2. ✅ Website bisa diakses (tidak 500)
3. ✅ Logs: "✓ Migrations completed successfully"
4. ✅ Database accessible (table ada)
5. ✅ No "localhost:3306" errors

---

## 📞 Need Help?

1. **Read**: `RAILWAY_DEPLOYMENT.md` - full guide
2. **Debug**: `DEBUG_LOCALHOST_ERROR.md` - error diagnosis
3. **Check**: Railway docs - https://railway.app/docs
4. **Ask**: Railway Discord - https://discord.gg/railway

Good luck! 🚀
