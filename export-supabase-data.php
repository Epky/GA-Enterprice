<?php
/**
 * Export Supabase Database to SQL File
 * This script exports your current Supabase data to a SQL file for import to localhost
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Supabase Data Export Tool ===\n\n";

// Temporarily use Supabase connection
config(['database.default' => 'supabase']);

try {
    // Test connection
    DB::connection('supabase')->getPdo();
    echo "✓ Connected to Supabase\n\n";
    
    // Get all tables
    $tables = DB::connection('supabase')
        ->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
    
    $sqlFile = 'supabase_export_' . date('Y-m-d_His') . '.sql';
    $handle = fopen($sqlFile, 'w');
    
    fwrite($handle, "-- Supabase Database Export\n");
    fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($handle, "SET client_encoding = 'UTF8';\n");
    fwrite($handle, "SET standard_conforming_strings = on;\n\n");
    
    echo "Exporting tables:\n";
    
    foreach ($tables as $table) {
        $tableName = $table->tablename;
        
        // Skip system tables
        if (in_array($tableName, ['migrations', 'failed_jobs', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
            echo "  ⊘ Skipping system table: $tableName\n";
            continue;
        }
        
        echo "  → Exporting: $tableName\n";
        
        // Get table structure
        $createTable = DB::connection('supabase')
            ->select("SELECT 'CREATE TABLE ' || quote_ident(tablename) || ' (' || 
                     string_agg(quote_ident(attname) || ' ' || format_type(atttypid, atttypmod), ', ') || ');' as create_sql
                     FROM pg_attribute a
                     JOIN pg_class c ON a.attrelid = c.oid
                     JOIN pg_namespace n ON c.relnamespace = n.oid
                     WHERE c.relname = ? AND n.nspname = 'public' AND a.attnum > 0 AND NOT a.attisdropped
                     GROUP BY tablename", [$tableName]);
        
        if (!empty($createTable)) {
            fwrite($handle, "\n-- Table: $tableName\n");
            fwrite($handle, $createTable[0]->create_sql . "\n\n");
        }
        
        // Get data
        $rows = DB::connection('supabase')->table($tableName)->get();
        
        if ($rows->count() > 0) {
            fwrite($handle, "-- Data for table: $tableName\n");
            
            foreach ($rows as $row) {
                $columns = array_keys((array)$row);
                $values = array_map(function($value) {
                    if (is_null($value)) return 'NULL';
                    if (is_bool($value)) return $value ? 'true' : 'false';
                    if (is_numeric($value)) return $value;
                    return "'" . addslashes($value) . "'";
                }, array_values((array)$row));
                
                $sql = "INSERT INTO " . $tableName . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                fwrite($handle, $sql);
            }
            
            fwrite($handle, "\n");
            echo "    ✓ Exported " . $rows->count() . " rows\n";
        } else {
            echo "    ⊘ No data\n";
        }
    }
    
    fclose($handle);
    
    echo "\n✓ Export completed successfully!\n";
    echo "✓ File saved: $sqlFile\n\n";
    echo "Next steps:\n";
    echo "1. Install PostgreSQL on your machine\n";
    echo "2. Create database: beauty_store\n";
    echo "3. Run migrations: php artisan migrate:fresh\n";
    echo "4. Import data: psql -U postgres -d beauty_store -f $sqlFile\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nMake sure your Supabase connection is still active in .env\n";
}
