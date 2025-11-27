<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Laravel system tables that MUST have RLS disabled or proper policies
     */
    private array $systemTables = [
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
        'personal_access_tokens',
        'migrations'
    ];

    /**
     * Run the migrations - Comprehensive RLS fix
     */
    public function up(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Log::info('=== Starting Comprehensive RLS Fix for System Tables ===');

        foreach ($this->systemTables as $table) {
            try {
                $this->fixTableRLS($table);
            } catch (\Exception $e) {
                Log::error("Failed to fix RLS for table '{$table}': " . $e->getMessage());
                // Continue with other tables
            }
        }

        Log::info('=== Completed Comprehensive RLS Fix ===');
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Log::info('=== Rolling back Comprehensive RLS Fix ===');

        foreach ($this->systemTables as $table) {
            try {
                if (!$this->tableExists($table)) {
                    continue;
                }

                // Remove all policies
                $this->dropAllPolicies($table);
                
                // Re-enable RLS (original state)
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                
                Log::info("Rolled back RLS fix for table '{$table}'");
            } catch (\Exception $e) {
                Log::error("Failed to rollback RLS for table '{$table}': " . $e->getMessage());
            }
        }

        Log::info('=== Completed Rollback ===');
    }

    /**
     * Fix RLS for a specific table
     */
    private function fixTableRLS(string $table): void
    {
        // Step 1: Check if table exists
        if (!$this->tableExists($table)) {
            Log::info("Table '{$table}' does not exist, skipping");
            return;
        }

        Log::info("Processing table '{$table}'");

        // Step 2: Get current RLS status
        $rlsEnabled = $this->isRLSEnabled($table);
        Log::info("Table '{$table}' RLS status: " . ($rlsEnabled ? 'ENABLED' : 'DISABLED'));

        // Step 3: Get existing policies
        $policies = $this->getExistingPolicies($table);
        Log::info("Table '{$table}' has " . count($policies) . " existing policies");

        // Step 4: STRATEGY - Disable RLS completely for system tables
        // This is the most reliable approach for Laravel system tables
        
        // First, drop ALL existing policies
        $this->dropAllPolicies($table);
        
        // Then, disable RLS
        DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        
        Log::info("✓ Successfully disabled RLS on table '{$table}'");

        // Step 5: Verify access
        if ($this->testTableAccess($table)) {
            Log::info("✓ Table '{$table}' is now accessible");
        } else {
            Log::warning("⚠ Table '{$table}' may still have access issues");
        }
    }

    /**
     * Check if table exists
     */
    private function tableExists(string $table): bool
    {
        $result = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            )
        ", [$table]);
        
        return $result[0]->exists ?? false;
    }

    /**
     * Check if RLS is enabled on table
     */
    private function isRLSEnabled(string $table): bool
    {
        $result = DB::select("
            SELECT relrowsecurity as enabled
            FROM pg_class
            WHERE relname = ?
        ", [$table]);

        return $result[0]->enabled ?? false;
    }

    /**
     * Get existing policies for a table
     */
    private function getExistingPolicies(string $table): array
    {
        $policies = DB::select("
            SELECT policyname
            FROM pg_policies
            WHERE tablename = ?
        ", [$table]);

        return array_map(fn($p) => $p->policyname, $policies);
    }

    /**
     * Drop all policies from a table
     */
    private function dropAllPolicies(string $table): void
    {
        $policies = $this->getExistingPolicies($table);
        
        foreach ($policies as $policyName) {
            try {
                DB::statement("DROP POLICY IF EXISTS \"{$policyName}\" ON {$table}");
                Log::info("Dropped policy '{$policyName}' from table '{$table}'");
            } catch (\Exception $e) {
                Log::warning("Could not drop policy '{$policyName}': " . $e->getMessage());
            }
        }
    }

    /**
     * Test if table is accessible
     */
    private function testTableAccess(string $table): bool
    {
        try {
            DB::select("SELECT 1 FROM {$table} LIMIT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
};
