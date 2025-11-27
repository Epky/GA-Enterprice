# Complete Database Migration Guide
## From Supabase to Localhost PostgreSQL

---

## Current Status
✓ Configuration files updated (.env and config/database.php)
✗ PostgreSQL not installed on your system yet

---

## Option 1: Install PostgreSQL (Recommended)

### Download & Install
1. Go to: https://www.postgresql.org/download/windows/
2. Download PostgreSQL 16 (or latest version)
3. Run the installer

### Installation Settings
- **Installation Directory**: Default (C:\Program Files\PostgreSQL\16)
- **Data Directory**: Default
- **Password**: Choose a strong password (you'll need this!)
- **Port**: 5432 (default)
- **Locale**: Default
- **Components**: Install all (PostgreSQL Server, pgAdmin, Command Line Tools)

### After Installation
1. Open **pgAdmin** (installed with PostgreSQL)
2. Connect to PostgreSQL Server (localhost)
3. Right-click "Databases" → Create → Database
4. Name: `beauty_store`
5. Click "Save"

---

## Option 2: Use XAMPP/Laragon (If you have it)

### If you have XAMPP:
- XAMPP doesn't include PostgreSQL by default
- You'll need to install PostgreSQL separately (see Option 1)

### If you have Laragon:
- Laragon can include PostgreSQL
- Check if it's already installed
- If not, download PostgreSQL addon for Laragon

---

## Migration Steps (After PostgreSQL is Installed)

### Step 1: Update .env Password
Open `.env` and set your PostgreSQL password:
```env
DB_PASSWORD=your_postgres_password_here
```

### Step 2: Test Connection
```bash
php artisan migrate:status
```

If you see an error, check:
- PostgreSQL service is running
- Database `beauty_store` exists
- Password in .env is correct

### Step 3: Run Migration Helper
```bash
php migrate-to-localhost.php
```

This interactive script will:
- Test both Supabase and localhost connections
- Export your Supabase data (optional)
- Run migrations on localhost
- Guide you through the process

### Step 4: Run Migrations
```bash
# Clear config cache
php artisan config:clear

# Run migrations
php artisan migrate:fresh

# Seed initial data (if you have seeders)
php artisan db:seed
```

### Step 5: Create Test User
```bash
php artisan tinker
```

Then in tinker:
```php
User::create([
    'name' => 'Admin',
    'email' => 'admin@test.com',
    'password' => bcrypt('password'),
    'role' => 'admin'
]);
exit
```

### Step 6: Test Your Application
```bash
php artisan serve
```

Visit: http://127.0.0.1:8000

---

## Troubleshooting

### Error: "could not connect to server"
**Solution:**
1. Check if PostgreSQL service is running:
   - Open Services (Win + R → services.msc)
   - Find "postgresql-x64-16" (or similar)
   - Status should be "Running"
   - If not, right-click → Start

### Error: "database does not exist"
**Solution:**
1. Open pgAdmin
2. Create database named `beauty_store`

### Error: "password authentication failed"
**Solution:**
1. Check DB_PASSWORD in .env matches your PostgreSQL password
2. Try connecting with pgAdmin using same password

### Error: "FATAL: role does not exist"
**Solution:**
1. Make sure DB_USERNAME=postgres in .env
2. Or create a new user in pgAdmin

### Migrations fail with RLS errors
**Solution:**
Some migrations have Supabase-specific RLS policies. You can:

**Option A:** Skip RLS migrations temporarily
- Comment out these files in `database/migrations/`:
  - `2025_11_04_091844_fix_migrations_table_rls.php`
  - `2025_11_28_000001_fix_public_table_rls_policies.php`
  - `2025_11_28_100000_enable_rls_all_tables.php`
  - `2025_11_28_120000_fix_users_table_rls_for_authentication.php`
  - `2025_11_28_130000_fix_users_table_rls_for_pooler.php`

**Option B:** Keep RLS (PostgreSQL supports it)
- RLS works on local PostgreSQL too
- Just run migrations normally

---

## Data Migration Options

### Option 1: Fresh Start (Recommended for Development)
```bash
php artisan migrate:fresh --seed
```
- Creates clean database
- Runs seeders to populate initial data
- Good for development/testing

### Option 2: Export from Supabase
Before changing .env to localhost, run:
```bash
php export-supabase-data.php
```
This creates a backup of your Supabase data.

### Option 3: Manual Export via pgAdmin
1. Connect to Supabase in pgAdmin
2. Right-click database → Backup
3. Save backup file
4. Connect to localhost in pgAdmin
5. Right-click database → Restore
6. Select backup file

---

## Quick Start Commands

```bash
# 1. Clear cache
php artisan config:clear

# 2. Test connection
php artisan migrate:status

# 3. Create tables
php artisan migrate:fresh

# 4. Seed data
php artisan db:seed

# 5. Start server
php artisan serve
```

---

## Need Help?

If you encounter issues:
1. Check PostgreSQL service is running
2. Verify database exists
3. Confirm password in .env
4. Run: `php migrate-to-localhost.php` for guided help

---

## Alternative: Use MySQL Instead

If you prefer MySQL (and have it installed):

1. Update .env:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beauty_store
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

2. Some migrations may need adjustments for MySQL syntax
3. Let me know if you want MySQL-specific migration help
