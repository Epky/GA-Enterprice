<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Disable RLS on Laravel system tables that exist
        $tables = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];
        
        foreach ($tables as $table) {
            // Check if table exists before disabling RLS
            $exists = DB::select("SELECT to_regclass('public.{$table}') as exists");
            if ($exists[0]->exists !== null) {
                DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Re-enable RLS on Laravel system tables that exist
        $tables = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];
        
        foreach ($tables as $table) {
            // Check if table exists before enabling RLS
            $exists = DB::select("SELECT to_regclass('public.{$table}') as exists");
            if ($exists[0]->exists !== null) {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            }
        }
    }
};
