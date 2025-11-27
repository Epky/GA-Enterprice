# Localhost Database Setup Guide

## Overview
This guide will help you migrate from Supabase to a local PostgreSQL database.

## Prerequisites

### Install PostgreSQL
Choose one based on your system:

**Windows:**
1. Download PostgreSQL from https://www.postgresql.org/download/windows/
2. Run the installer (recommended version: 15 or 16)
3. During installation:
   - Set password for postgres user (remember this!)
   - Default port: 5432
   - Install pgAdmin (optional GUI tool)

**Alternative - Using XAMPP/Laragon:**
- If you have XAMPP, it doesn't include PostgreSQL by default
- Consider using Laragon which includes PostgreSQL option

## Setup Steps

### 1. Create Database

**Option A: Using pgAdmin (GUI)**
1. Open pgAdmin
2. Connect to PostgreSQL server (localhost)
3. Right-click "Databases" → Create → Database
4. Name: `beauty_store`
5. Click Save

**Option B: Using Command Line**
```bash
# Open Command Prompt or PowerShell
psql -U postgres

# In psql prompt:
CREATE DATABASE beauty_store;
\q
```

### 2. Update .env File

Your .env has been updated with these settings:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=beauty_store
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password_here
DB_SSLMODE=prefer
```

**IMPORTANT:** Update `DB_PASSWORD` with your PostgreSQL password!

### 3. Run Migrations

```bash
# Clear config cache
php artisan config:clear

# Test database connection
php artisan migrate:status

# Run migrations to create tables
php artisan migrate:fresh

# Seed initial data
php artisan db:seed
```

### 4. Remove Supabase-Specific Code (Optional)

Since you're moving to localhost, you can disable Supabase-specific features:

**In .env, comment out or remove:**
```
# SUPABASE_URL=...
# SUPABASE_ANON_KEY=...
# SUPABASE_SERVICE_KEY=...
```

## Troubleshooting

### Connection Failed
- Verify PostgreSQL service is running
- Check Windows Services → PostgreSQL should be "Running"
- Verify password in .env matches your PostgreSQL password

### Port Already in Use
- Default PostgreSQL port is 5432
- If another service uses it, change DB_PORT in .env

### Migration Errors with RLS Policies
Some migrations have Supabase RLS (Row Level Security) policies. You have two options:

**Option 1: Skip RLS migrations**
```bash
# Comment out or skip these migrations:
# - 2025_11_04_090743_enable_beauty_store_rls_policies.php
# - 2025_11_28_000001_fix_public_table_rls_policies.php
# - 2025_11_28_100000_enable_rls_all_tables.php
# - 2025_11_28_120000_fix_users_table_rls_for_authentication.php
# - 2025_11_28_130000_fix_users_table_rls_for_pooler.php
```

**Option 2: Keep RLS (PostgreSQL supports it)**
- RLS works on local PostgreSQL too
- Just run migrations normally

## Differences from Supabase

### What Changes:
- ❌ No Supabase Auth (use Laravel's built-in auth)
- ❌ No Supabase Storage API
- ❌ No automatic RLS with JWT tokens
- ✅ Full control over database
- ✅ Faster local development
- ✅ No internet required

### What Stays the Same:
- ✅ All your tables and data structure
- ✅ PostgreSQL features (JSON, arrays, etc.)
- ✅ Laravel Eloquent models work identically
- ✅ All your queries and relationships

## Next Steps

1. Install PostgreSQL
2. Create `beauty_store` database
3. Update DB_PASSWORD in .env
4. Run `php artisan migrate:fresh --seed`
5. Test your application: `php artisan serve`

## Need MySQL Instead?

If you prefer MySQL over PostgreSQL, let me know and I'll provide MySQL-specific setup instructions and migration adjustments.
