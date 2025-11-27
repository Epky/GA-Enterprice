# 🔧 Supabase Connection Setup Guide

## Current Problem

Your Laravel app cannot connect to Supabase because:
- ❌ **Direct connection fails**: Windows PHP doesn't support IPv6 DNS resolution
- ❌ **Pooler connection fails**: "Tenant or user not found" error

## ✅ Solution: Enable Supabase Connection Pooler

### Step 1: Enable Connection Pooler in Supabase Dashboard

1. **Go to your Supabase Dashboard:**
   ```
   https://supabase.com/dashboard/project/hgmdtzpsbzwanjuhiemf/settings/database
   ```

2. **Scroll down to "Connection Pooling" section**

3. **Enable Connection Pooling** if it's not already enabled

4. **Copy the Connection String** for **Transaction mode** (recommended for Laravel)
   - It should look like:
     ```
     postgresql://postgres.[PROJECT-REF]:[PASSWORD]@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres
     ```
   - Or for Session mode (port 5432):
     ```
     postgresql://postgres.[PROJECT-REF]:[PASSWORD]@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres
     ```

### Step 2: Update Your .env File

Once you have the correct connection string from Supabase, update your `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.[YOUR-PROJECT-REF]
DB_PASSWORD=[YOUR-PASSWORD]
DB_SSLMODE=require
```

**Replace:**
- `[YOUR-PROJECT-REF]` with your actual project reference (e.g., `hgmdtzpsbzwanjuhiemf`)
- `[YOUR-PASSWORD]` with your actual database password

### Step 3: Clear Laravel Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 4: Test Connection

```bash
php artisan migrate:status
```

Or run the test script:
```bash
php test-supabase-connection.php
```

## Alternative: Use Supavisor (Newer Pooler)

If the above doesn't work, Supabase might be using the newer Supavisor pooler:

1. Check your dashboard for connection strings starting with:
   - `aws-0-ap-southeast-1.pooler.supabase.com` (PgBouncer - older)
   - Or a different format for Supavisor

2. The username format might be different:
   - PgBouncer: `postgres.PROJECT_REF`
   - Supavisor: `postgres` or `postgres.PROJECT_REF`

## Troubleshooting

### Issue: "Tenant or user not found"

**Possible causes:**
1. Connection pooling is not enabled in your Supabase project
2. Wrong username format
3. Wrong pooler hostname

**Solutions:**
- ✅ Enable connection pooling in Supabase dashboard
- ✅ Copy the exact connection string from the dashboard
- ✅ Don't manually construct the connection string

### Issue: "Connection timed out"

**Possible causes:**
1. Firewall blocking port 6543 or 5432
2. Antivirus blocking connection

**Solutions:**
- ✅ Check Windows Firewall settings
- ✅ Temporarily disable antivirus
- ✅ Try using a VPN

### Issue: Still can't connect

**Last resort options:**

#### Option 1: Use IPv6 Tunnel (Advanced)
Install Teredo or 6to4 tunnel to enable IPv6 on Windows

#### Option 2: Use Cloudflare Tunnel
Set up a Cloudflare tunnel to proxy the connection

#### Option 3: Use Docker
Run your Laravel app in Docker with IPv6 support:
```yaml
# docker-compose.yml
services:
  app:
    image: php:8.2-fpm
    networks:
      - app-network
networks:
  app-network:
    enable_ipv6: true
```

#### Option 4: Deploy to Production
Deploy your app to a server with IPv6 support (most cloud providers support it)

## Connection Modes Comparison

| Mode | Port | Hostname | Use Case | Prepared Statements |
|------|------|----------|----------|---------------------|
| Direct | 5432 | db.*.supabase.co | IPv6 only | ✅ Full |
| Transaction Pooler | 6543 | pooler.supabase.com | Laravel (recommended) | ⚠️ Limited |
| Session Pooler | 5432 | pooler.supabase.com | Complex queries | ✅ Full |

## What to Do Next

1. **Go to Supabase Dashboard** and enable connection pooling
2. **Copy the exact connection string** from the dashboard
3. **Update your .env** with the correct credentials
4. **Test the connection** using `php artisan migrate:status`

If you're still having issues, please:
- Share a screenshot of your Supabase database settings page
- Check if connection pooling is available for your plan
- Contact Supabase support if pooling isn't available

## Quick Reference

Your Supabase Project:
- **Project Ref**: `hgmdtzpsbzwanjuhiemf`
- **Region**: `ap-southeast-1` (Singapore)
- **Dashboard**: https://supabase.com/dashboard/project/hgmdtzpsbzwanjuhiemf

Test script location: `test-supabase-connection.php`
Documentation: `docs/SUPABASE_CONNECTION_FIX.md`
