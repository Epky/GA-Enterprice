<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Fix migrations table RLS
     */
    public function up(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Enable RLS on migrations table
        DB::statement("ALTER TABLE migrations ENABLE ROW LEVEL SECURITY");
        
        // Allow service_role full access to migrations table
        DB::statement("
            CREATE POLICY service_role_migrations_access ON migrations
            FOR ALL TO service_role
            USING (true)
            WITH CHECK (true)
        ");
        
        // Also check if there are any other system tables that need RLS
        $systemTables = ['password_reset_tokens', 'personal_access_tokens', 'failed_jobs'];
        
        foreach ($systemTables as $table) {
            // Check if table exists first
            $exists = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)", [$table]);
            
            if ($exists[0]->exists) {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                DB::statement("
                    CREATE POLICY service_role_{$table}_access ON {$table}
                    FOR ALL TO service_role
                    USING (true)
                    WITH CHECK (true)
                ");
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

        // Drop policies and disable RLS
        DB::statement("DROP POLICY IF EXISTS service_role_migrations_access ON migrations");
        DB::statement("ALTER TABLE migrations DISABLE ROW LEVEL SECURITY");
        
        $systemTables = ['password_reset_tokens', 'personal_access_tokens', 'failed_jobs'];
        
        foreach ($systemTables as $table) {
            $exists = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)", [$table]);
            
            if ($exists[0]->exists) {
                DB::statement("DROP POLICY IF EXISTS service_role_{$table}_access ON {$table}");
                DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            }
        }
    }
};