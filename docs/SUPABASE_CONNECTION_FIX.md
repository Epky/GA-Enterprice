# Supabase Connection Fix - IPv6 DNS Resolution Issue

## Problem
Your Laravel application cannot connect to Supabase because:
1. Supabase direct database connections use IPv6-only addresses
2. Windows PHP/PostgreSQL doesn't properly support IPv6 DNS resolution
3. Error: `could not translate host name "db.hgmdtzpsbzwanjuhiemf.supabase.co" to address: Unknown host`

## Solution: Use Supabase Connection Pooler

### Step 1: Get Your Pooler Connection String

1. Go to your Supabase Dashboard: https://supabase.com/dashboard/project/hgmdtzpsbzwanjuhiemf
2. Click on **Settings** → **Database**
3. Scroll down to **Connection Pooling**
4. Look for **Connection string** under "Transaction" or "Session" mode
5. Copy the connection string - it should look like:
   ```
   postgresql://postgres.[PROJECT-REF]:[PASSWORD]@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres
   ```

### Step 2: Update Your .env File

Replace your current database configuration with the pooler settings:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.[YOUR-PROJECT-REF]
DB_PASSWORD=[YOUR-PASSWORD]
DB_SSLMODE=require
```

**Important Notes:**
- Port `6543` is for **Transaction mode** (recommended for Laravel)
- Port `5432` is for **Session mode** (use if you need prepared statements)
- Username format: `postgres.[project-ref]` (e.g., `postgres.hgmdtzpsbzwanjuhiemf`)
- The pooler hostname resolves to IPv4, which works on Windows

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

## Alternative Solution: Enable IPv6 on Windows

If you prefer to use direct connection (not recommended):

1. Open Command Prompt as Administrator
2. Run: `netsh interface ipv6 show interfaces`
3. If IPv6 is disabled, enable it:
   ```cmd
   netsh interface ipv6 set global state=enabled
   ```
4. Restart your computer
5. Test DNS resolution: `nslookup db.hgmdtzpsbzwanjuhiemf.supabase.co`

## Troubleshooting

### Error: "Tenant or user not found"
- Check your username format - it should be `postgres.[project-ref]`
- Verify your password is correct
- Make sure you're using the pooler hostname, not the direct database hostname

### Error: "Connection timed out"
- Check your firewall settings
- Verify port 6543 (or 5432) is not blocked
- Try disabling antivirus temporarily

### Error: "SSL connection required"
- Set `DB_SSLMODE=require` in your .env file
- Make sure your PHP has OpenSSL enabled

## Connection Modes Comparison

| Mode | Port | Use Case | Prepared Statements |
|------|------|----------|---------------------|
| Transaction | 6543 | Laravel (recommended) | Limited |
| Session | 5432 | Complex queries | Full support |
| Direct | 5432 | IPv6 only | Full support |

## Verification

After applying the fix, verify your connection:

```bash
# Test database connection
php artisan db:show

# Run a simple query
php artisan tinker
>>> DB::select('SELECT version()');
```

## Additional Resources

- [Supabase Connection Pooling Docs](https://supabase.com/docs/guides/database/connecting-to-postgres#connection-pooler)
- [Laravel Database Configuration](https://laravel.com/docs/database#configuration)
