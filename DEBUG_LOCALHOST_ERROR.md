# Debugging Error: "Host: localhost, Port: 3306"

## 🔴 Root Cause Error

Dari logs Railway kamu:
```
SQLSTATE[HY000] [2002] No such file or directory 
(Connection: mysql, Host: localhost, Port: 3306, Database: crypto_waf, ...)
```

Ini berarti:
- ❌ Laravel masih membaca `DB_HOST=127.0.0.1` (default di code)
- ❌ Environment variables dari Railway TIDAK ter-load
- ❌ MySQL service di Railway mungkin belum connected atau variables belum ter-set

---

## 🔍 Diagnosis Checklist

### Step 1: Pastikan MySQL Service Running
1. Buka Railway Dashboard
2. Lihat project services:
   - PHP app service → Status harus "Running" ✓
   - MySQL service → Status harus "Running" ✓

Jika MySQL status "Crashed" atau "Building", tunggu beberapa menit.

### Step 2: Cek MySQL Service Variables
1. Railway Dashboard → Click MySQL service box
2. Pindah ke tab "Variables"
3. Harus ada variables seperti:
   ```
   DATABASE_URL=mysql://root:password@container:3306/railway
   MYSQL_HOST=container
   MYSQL_USER=root
   MYSQL_PASSWORD=...password
   MYSQL_DATABASE=railway
   MYSQL_PORT=3306
   ```

Jika kosong/tidak ada, database belum fully initialized. Tunggu 2-3 menit.

### Step 3: Cek App Service Variables
1. Railway Dashboard → Click PHP app service
2. Pindah ke tab "Variables"
3. **HARUS ADA** minimal:
   ```
   DATABASE_URL=${{Mysql.DATABASE_URL}}
   ```
   atau
   ```
   DB_HOST=${{Mysql.MYSQL_HOST}}
   DB_PORT=${{Mysql.MYSQL_PORT}}
   DB_DATABASE=${{Mysql.MYSQL_DATABASE}}
   DB_USERNAME=${{Mysql.MYSQL_USER}}
   DB_PASSWORD=${{Mysql.MYSQL_PASSWORD}}
   ```

Jika tidak ada → **INI MASALAHNYA!**

Kamu harus tambahkan variables ini secara manual di Railway UI.

---

## ✅ Solution: Add Variables Manually di Railway

### Method 1: Using DATABASE_URL (RECOMMENDED)

1. Railway Dashboard → Click PHP app service
2. Variables tab → Click "New" atau "Add Variable"
3. Tambahin:
   ```
   DATABASE_URL=${{Mysql.DATABASE_URL}}
   ```
4. Rails akan auto-reference `DATABASE_URL` dari MySQL service
5. Save dan re-deploy

### Method 2: Individual MYSQL_* Variables

Jika Method 1 tidak bekerja, try ini:

1. Railway Dashboard → Click MySQL service
2. Copy semua variables dari panel Variables
3. Click PHP app service → Variables
4. Tambahkan semua ini:
   ```
   DB_CONNECTION=mysql
   DB_HOST=${{Mysql.MYSQL_HOST}}
   DB_PORT=${{Mysql.MYSQL_PORT}}
   DB_DATABASE=${{Mysql.MYSQL_DATABASE}}
   DB_USERNAME=${{Mysql.MYSQL_USER}}
   DB_PASSWORD=${{Mysql.MYSQL_PASSWORD}}
   ```
5. Save dan re-deploy

---

## 🔄 Re-deploy Setelah Edit Variables

Setelah tambah variables:

1. Railway Dashboard → Deployments
2. Klik tombol "Redeploy" atau
3. Push commit baru ke GitHub:
   ```bash
   git add .
   git commit -m "Update deployment"
   git push origin main
   ```

---

## 📝 Expected Behavior After Fix

Saat deployment, seharusnya log menunjukkan:
```
✓ DATABASE_URL is set: mysql://root:pass@container:3306/railway
✓ Migrations completed successfully
✓ Setup completed!
```

Kalau masih error, cek section "Still Getting Error?" di bawah.

---

## ❓ Still Getting Error?

### Error: "Same error as before"

**Mungkin**: Variables masih cached atau deployment belum fully updated

**Solusi**:
1. Railway Dashboard → Project Settings → Environment
2. Klik "Rebuild"
3. atau: Manual redeploy dari "Deployments" tab

### Error: "Connection refused" atau "timeout"

**Mungkin**: MySQL service belum fully ready

**Solusi**:
1. Check MySQL service status → harus "Running"
2. Tunggu 5 menit
3. Click "Redeploy" di deployments

### Error: "Unknown database"

**Kemungkinan**: DATABASE_URL atau DB_DATABASE salah

**Solusi**:
1. Cek MySQL variables → lihat `MYSQL_DATABASE` value
2. Pastikan sama dengan `DB_DATABASE` di app variables
3. Re-deploy

---

## 🐛 Debug Commands (Via Railway Shell)

Jika masih stuck, bisa test manually via Railway SSH:

1. Railway Dashboard → Click PHP app
2. Click "..." menu → "SSH"
3. Run commands:

```bash
# Check environment variables
env | grep -i mysql
env | grep -i database

# Check config
php artisan config:show database

# Test connection
php artisan tinker
>>> DB::connection()->getPdo()
```

Ini akan show MySQL connection details yang sedang dipakai.

---

## 📞 Last Resort: Forum Railroad

Jika masih error setelah semua ini:
1. Buka Railway Discord: https://discord.gg/railway
2. Tanyakan di support channel
3. Share screenshot dari Variables tab

Mereka bisa help dengan Railway-specific issues.
