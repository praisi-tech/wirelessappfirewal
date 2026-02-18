# Vercel Deployment Guide for Crypto-WAF

## Quick Fix for "Download" Issue

If your Vercel deployment is downloading files instead of rendering the website, follow these steps:

### Step 1: Update Configuration

The `vercel.json` has been updated with the correct Laravel configuration. Ensure you have:

```json
{
  "version": 2,
  "buildCommand": "npm ci && npm run build && composer install --no-dev --optimize-autoloader && php artisan optimize",
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.7.1"
    }
  },
  "routes": [
    {
      "src": "^/(favicon\\.ico|robots\\.txt|sitemap\\.xml)$",
      "dest": "/public/$1"
    },
    {
      "src": "^/build/(.*)$",
      "dest": "/public/build/$1"
    },
    {
      "src": "^/css/(.*)$",
      "dest": "/public/css/$1"
    },
    {
      "src": "^/js/(.*)$",
      "dest": "/public/js/$1"
    },
    {
      "src": "^/images/(.*)$",
      "dest": "/public/images/$1"
    },
    {
      "src": "^/storage/(.*)$",
      "dest": "/storage/$1"
    },
    {
      "src": "^/.*",
      "dest": "/api/index.php"
    }
  ]
}
```

### Step 2: Trigger Fresh Deployment

Do ONE of the following:

**Option A: Push a new commit**
```bash
git add .
git commit -m "Fix Vercel deployment configuration"
git push origin main
```

**Option B: Redeploy from Vercel Dashboard**
1. Go to https://vercel.com
2. Select your Crypto-WAF project
3. Click "Deployments" tab
4. Find the latest deployment
5. Click "..." menu → "Redeploy"

**Option C: Force rebuild**
```bash
vercel deploy --prod --force
```

### Step 3: Verify Environment Variables

In Vercel Dashboard, go to Settings → Environment Variables and ensure:

```
APP_KEY = base64:UpXvOgYqZsUHzTSHQqPneSkpxVFO+vjxcI1HmXHvtfUm=
APP_ENV = production
APP_DEBUG = false
APP_URL = https://crypto-waf.vercel.app
CACHE_DRIVER = array
SESSION_DRIVER = cookie
LOG_CHANNEL = stderr
```

### Step 4: Clear Vercel Cache (if needed)

If still downloading after redeploy:
1. Vercel Dashboard → Project Settings
2. Go to "Storage" section
3. Click "Clear All" on any build caches
4. Redeploy again

---

## Troubleshooting

### Still Downloading?

**Check 1: Verify api/index.php exists**
```bash
# Should output: <?php ... require __DIR__ . '/../public/index.php';
cat api/index.php
```

**Check 2: Verify public/index.php exists**
```bash
# Should exist and be ~23 lines
ls -lh public/index.php
```

**Check 3: Check Vercel build logs**
1. Go to Vercel Dashboard
2. Select Crypto-WAF project
3. Click latest Deployment
4. View logs for errors

**Check 4: Verify .env.production is not in .gitignore**
```bash
# Should NOT be in gitignore
grep ".env.production" .gitignore
```

### Common Issues

#### Issue: "Cannot find api/index.php"
**Solution**: Ensure git is tracking it
```bash
git add api/index.php
git commit -m "Add Vercel entry point"
git push
```

#### Issue: "Composer install fails on Vercel"
**Solution**: Clear Vercel cache and redeploy
1. Dashboard → Settings → Storage
2. Click "Clear All"
3. Redeploy

#### Issue: "404 Not Found on all routes"
**Solution**: The routes aren't matching. Verify:
- vercel.json routes are in correct order
- The catch-all route `"src": "^/.*"` is last

---

## File Checklist

Ensure these files exist:

```
✅ api/index.php                 (Vercel entry point)
✅ public/index.php              (Laravel entry point)
✅ public/favicon.ico            (Browser favorite icon)
✅ vercel.json                   (Deployment config)
✅ .env.production               (Production env vars)
✅ composer.json                 (PHP dependencies)
✅ package.json                  (Node dependencies)
✅ composer.lock                 (Locked dependencies)
```

All of these should be committed to Git:
```bash
git status
```

---

## Deployment Flow

```
1. Push code to GitHub
   ↓
2. Vercel detects push
   ↓
3. Build Phase:
   ├─ npm ci && npm run build
   ├─ composer install --no-dev --optimize-autoloader
   └─ php artisan optimize
   ↓
4. Deployment Phase:
   ├─ Create serverless functions (api/index.php)
   ├─ Setup routes
   └─ Configure environment variables
   ↓
5. Live at https://crypto-waf.vercel.app
```

---

## Manual Testing

Once deployed, test these URLs:

```
✅ https://crypto-waf.vercel.app/           - Should show Welcome page
✅ https://crypto-waf.vercel.app/login      - Should show Login form
✅ https://crypto-waf.vercel.app/register   - Should show Register form
❌ https://crypto-waf.vercel.app/notfound   - Should show 404 page
```

If any return a download, the PHP isn't executing.

---

## Next Steps

If deployment is successful:

1. **Setup Database** (required for full functionality)
   - The app needs MySQL for logs and user data
   - Vercel can't run databases, so use:
     - PlanetScale (MySQL compatible)
     - Supabase (PostgreSQL)
     - AWS RDS
     - Digital Ocean Managed MySQL

2. **Configure Environment Variables**
   - Add DB_HOST, DB_USERNAME, DB_PASSWORD in Vercel
   - Run migrations on connected database
   - Set APP_URL to your Vercel domain

3. **Setup Storage**
   - For file uploads, configure:
     - AWS S3
     - Google Cloud Storage
     - Backblaze B2

---

## Support

If still downloading after following all steps:

1. **Check Vercel Build Logs**:
   - Click Deployment → Logs
   - Look for PHP errors
   - Check build command output

2. **Check Runtime Version**:
   - Vercel dashboard → Settings → Runtime
   - Should say "Node" and "PHP 8.2"

3. **Check Route Matching**:
   - Test if `/api/index.php` is accessible
   - Verify routes in vercel.json don't have typos

4. **Try Redeploying**:
   - Sometimes Vercel cache causes issues
   - Clear all caches and redeploy

---

**Last Updated**: February 19, 2026  
**Crypto-WAF Version**: 1.0.0  
**Framework**: Laravel 11  
**PHP Version**: 8.2
