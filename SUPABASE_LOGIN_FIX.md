# Supabase Login Error Fix

## Problem

**Error:** `SQLSTATE[08006] [7] FATAL: Tenant or user not found`

This error occurs when trying to log in because:
1. Row Level Security (RLS) is enabled on the `users` table
2. The connection pooler (port 6543) doesn't allow `SET ROLE` commands
3. Laravel tries to query the users table without proper RLS context

## Root Cause

When using Supabase's **connection pooler** (port 6543), you cannot execute `SET ROLE` commands to switch to `service_role` and bypass RLS. This causes authentication queries to fail with "Tenant or user not found" because the database user doesn't have permission to read the users table through RLS policies.

## Solutions

### Solution 1: Grant BYPASSRLS Privilege (Recommended)

Grant your database user the ability to bypass RLS entirely:

```sql
-- Run this in Supabase SQL Editor
ALTER USER "postgres.hgmdtzpsbzwanjuhiemf" WITH BYPASSRLS;
```

**Pros:**
- Simple one-line fix
- Works with connection pooler
- Laravel can access all tables for system operations

**Cons:**
- Requires superuser privileges to execute
- Bypasses RLS for all operations from this user

### Solution 2: Create Permissive RLS Policies

Create RLS policies that allow public access to the users table:

```sql
-- Run the fix-supabase-login-rls.sql script in Supabase SQL Editor
```

See `fix-supabase-login-rls.sql` for the complete script.

**Pros:**
- More granular control
- Doesn't require superuser privileges
- Can be customized per table

**Cons:**
- More complex
- Need to create policies for each table Laravel accesses

### Solution 3: Use Direct Connection (Development Only)

Switch from connection pooler to direct connection:

```env
# Change from pooler (port 6543)
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=6543

# To direct connection (port 5432) - remove '.pooler' from hostname
DB_HOST=aws-0-ap-southeast-1.supabase.com
DB_PORT=5432
```

**Pros:**
- Allows `SET ROLE` commands
- Middleware can switch to service_role

**Cons:**
- Direct connections are limited (only 60 concurrent connections)
- Not recommended for production
- May have connection limits

### Solution 4: Use Service Role Key in Connection String

Configure the connection to use service_role from the start:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.hgmdtzpsbzwanjuhiemf
DB_PASSWORD=edselsuraltapayan26
# Add role parameter to connection options
DB_OPTIONS="--set role=service_role"
```

Then update `config/database.php`:

```php
'pgsql' => [
    // ... other config
    'options' => [
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false,
        PDO::PGSQL_ATTR_INIT_COMMAND => env('DB_OPTIONS', ''),
    ],
],
```

## Recommended Approach

**For Production:** Use **Solution 1** (BYPASSRLS) - it's the simplest and most reliable.

**For Development:** Use **Solution 3** (Direct Connection) if you need to test RLS policies.

## Implementation Steps

### Quick Fix (5 minutes)

1. Open your Supabase project dashboard
2. Go to SQL Editor
3. Run this command:
   ```sql
   ALTER USER "postgres.hgmdtzpsbzwanjuhiemf" WITH BYPASSRLS;
   ```
4. Try logging in again - it should work immediately

### Complete Fix (if BYPASSRLS doesn't work)

1. Run the complete SQL script:
   ```bash
   # Copy the contents of fix-supabase-login-rls.sql
   # Paste into Supabase SQL Editor
   # Execute
   ```

2. Verify the fix:
   ```bash
   php test-login-fix.php
   ```

3. Test login in your application

## Files Modified

- `app/Http/Middleware/SetSupabaseContext.php` - Updated to handle pooler connections
- `app/Providers/AppServiceProvider.php` - Removed problematic beforeExecuting hook
- `fix-supabase-login-rls.sql` - SQL script to fix RLS policies
- `test-login-fix.php` - Test script to verify the fix

## Verification

After applying the fix, verify it works:

1. Clear Laravel caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. Try logging in through the web interface

3. Check logs for any remaining errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Troubleshooting

### Still getting "Tenant or user not found"?

1. Verify your database user has BYPASSRLS:
   ```sql
   SELECT rolname, rolbypassrls FROM pg_roles WHERE rolname LIKE 'postgres%';
   ```

2. Check if RLS is enabled on users table:
   ```sql
   SELECT relname, relrowsecurity FROM pg_class WHERE relname = 'users';
   ```

3. Verify RLS policies:
   ```sql
   SELECT * FROM pg_policies WHERE tablename = 'users';
   ```

### Connection timeout issues?

- Make sure you're using the correct port (6543 for pooler, 5432 for direct)
- Verify your credentials in `.env` file
- Check Supabase project status

## Additional Notes

- The middleware `SetSupabaseContext` attempts to set service_role but gracefully handles failures
- With BYPASSRLS granted, the middleware's SET ROLE command becomes unnecessary but harmless
- All Laravel system tables (sessions, cache, jobs, etc.) will also work properly with this fix

## References

- [Supabase Connection Pooling](https://supabase.com/docs/guides/database/connecting-to-postgres#connection-pooler)
- [PostgreSQL Row Level Security](https://www.postgresql.org/docs/current/ddl-rowsecurity.html)
- [Laravel Database Configuration](https://laravel.com/docs/database#configuration)
