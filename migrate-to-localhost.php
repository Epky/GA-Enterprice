<?php
/**
 * Migrate Database from Supabase to Localhost
 * Step-by-step migration helper
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   Database Migration: Supabase → Localhost PostgreSQL     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Check Supabase connection
echo "Step 1: Testing Supabase connection...\n";
try {
    config(['database.default' => 'supabase']);
    DB::connection('supabase')->getPdo();
    echo "  ✓ Supabase connection successful\n\n";
    
    // Get table count
    $tables = DB::connection('supabase')
        ->select("SELECT COUNT(*) as count FROM pg_tables WHERE schemaname = 'public'");
    echo "  → Found " . $tables[0]->count . " tables in Supabase\n\n";
    
} catch (Exception $e) {
    echo "  ✗ Cannot connect to Supabase: " . $e->getMessage() . "\n";
    echo "  Note: This is expected if you already switched to localhost\n\n";
}

// Step 2: Check localhost PostgreSQL
echo "Step 2: Testing localhost PostgreSQL connection...\n";
try {
    config(['database.default' => 'pgsql']);
    DB::connection('pgsql')->getPdo();
    echo "  ✓ Localhost PostgreSQL connection successful\n";
    echo "  → Database: " . config('database.connections.pgsql.database') . "\n\n";
    
    // Check if database has tables
    $localTables = DB::connection('pgsql')
        ->select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'public'");
    
    if ($localTables[0]->count > 0) {
        echo "  ⚠ Warning: Database already has " . $localTables[0]->count . " tables\n";
        echo "  You may want to run: php artisan migrate:fresh\n\n";
    } else {
        echo "  → Database is empty (ready for migration)\n\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Cannot connect to localhost PostgreSQL\n";
    echo "  Error: " . $e->getMessage() . "\n\n";
    echo "  Please ensure:\n";
    echo "  1. PostgreSQL is installed\n";
    echo "  2. PostgreSQL service is running\n";
    echo "  3. Database 'beauty_store' exists\n";
    echo "  4. DB_PASSWORD in .env is correct\n\n";
    echo "  To create database, run in psql:\n";
    echo "  CREATE DATABASE beauty_store;\n\n";
    exit(1);
}

// Step 3: Export data from Supabase
echo "Step 3: Export data from Supabase?\n";
echo "  This will create a JSON backup of your Supabase data\n";
echo "  Type 'yes' to continue, or 'skip' to skip: ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) === 'yes') {
    echo "\n  Exporting data...\n";
    
    try {
        config(['database.default' => 'supabase']);
        
        $exportData = [];
        $tables = DB::connection('supabase')
            ->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        
        foreach ($tables as $table) {
            $tableName = $table->tablename;
            
            // Skip system tables
            if (in_array($tableName, ['migrations', 'failed_jobs', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
                continue;
            }
            
            echo "    → Exporting: $tableName\n";
            $data = DB::connection('supabase')->table($tableName)->get()->toArray();
            $exportData[$tableName] = $data;
            echo "      ✓ " . count($data) . " rows\n";
        }
        
        $filename = 'supabase_backup_' . date('Y-m-d_His') . '.json';
        file_put_contents($filename, json_encode($exportData, JSON_PRETTY_PRINT));
        
        echo "\n  ✓ Data exported to: $filename\n\n";
        
    } catch (Exception $e) {
        echo "  ✗ Export failed: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "  → Skipped data export\n\n";
}

// Step 4: Run migrations on localhost
echo "Step 4: Run migrations on localhost?\n";
echo "  This will create all tables in your local database\n";
echo "  Type 'yes' to continue, or 'skip' to skip: ";

$line = trim(fgets($handle));

if (strtolower($line) === 'yes') {
    echo "\n  Running migrations...\n";
    
    config(['database.default' => 'pgsql']);
    
    // Run migrate:fresh
    $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
    
    if ($exitCode === 0) {
        echo "  ✓ Migrations completed successfully\n\n";
    } else {
        echo "  ✗ Migration failed\n";
        echo Artisan::output();
        echo "\n";
    }
} else {
    echo "  → Skipped migrations\n\n";
}

fclose($handle);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    Migration Summary                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Your database configuration is now set to localhost.\n\n";

echo "Next steps:\n";
echo "1. If you exported data, you can import it manually\n";
echo "2. Or run seeders: php artisan db:seed\n";
echo "3. Create a test user: php artisan tinker\n";
echo "   User::create(['name'=>'Admin','email'=>'admin@test.com','password'=>bcrypt('password'),'role'=>'admin']);\n";
echo "4. Start your app: php artisan serve\n\n";

echo "✓ Migration helper completed!\n";
