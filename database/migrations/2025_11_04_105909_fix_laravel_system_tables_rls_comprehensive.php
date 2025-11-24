<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Laravel system tables that need RLS configuration
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
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip RLS for SQLite (only works with PostgreSQL/Supabase)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Log::info('Starting comprehensive RLS fix for Laravel system tables');

        foreach ($this->systemTables as $table) {
            try {
                // Check if table exists
                $tableExists = $this->tableExists($table);
                
                if (!$tableExists) {
                    Log::info("Table '{$table}' does not exist, skipping");
                    continue;
                }

                // Check current RLS status
                $rlsStatus = $this->checkRLSStatus($table);
                Log::info("Table '{$table}' RLS status", $rlsStatus);

                // Fix RLS policies for this table
                $this->fixTableRLS($table, $rlsStatus);
                
                Log::info("Successfully fixed RLS for table '{$table}'");
            } catch (\Exception $e) {
                Log::error("Failed to fix RLS for table '{$table}': " . $e->getMessage());
                // Continue with other tables instead of failing completely
            }
        }

        Log::info('Completed comprehensive RLS fix for Laravel system tables');
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

        Log::info('Rolling back comprehensive RLS fix for Laravel system tables');

        foreach ($this->systemTables as $table) {
            try {
                if (!$this->tableExists($table)) {
                    continue;
                }

                // Remove service role policies
                $this->removeServiceRolePolicies($table);
                
                Log::info("Successfully rolled back RLS policies for table '{$table}'");
            } catch (\Exception $e) {
                Log::error("Failed to rollback RLS for table '{$table}': " . $e->getMessage());
            }
        }

        Log::info('Completed rollback of comprehensive RLS fix');
    }

    /**
     * Check if a table exists
     */
    private function tableExists(string $table): bool
    {
        $result = DB::select("SELECT to_regclass('public.{$table}') as exists");
        return $result[0]->exists !== null;
    }

    /**
     * Check RLS status for a table
     */
    private function checkRLSStatus(string $table): array
    {
        // Check if RLS is enabled
        $rlsEnabled = DB::select("
            SELECT relrowsecurity as enabled
            FROM pg_class
            WHERE relname = ?
        ", [$table]);

        // Get existing policies
        $policies = DB::select("
            SELECT policyname, cmd, roles::text, qual, with_check
            FROM pg_policies
            WHERE tablename = ?
        ", [$table]);

        return [
            'rls_enabled' => $rlsEnabled[0]->enabled ?? false,
            'policies' => $policies,
            'has_service_role_policy' => $this->hasServiceRolePolicy($policies)
        ];
    }

    /**
     * Check if service role policy exists
     */
    private function hasServiceRolePolicy(array $policies): bool
    {
        foreach ($policies as $policy) {
            if (str_contains($policy->roles, 'service_role') && 
                str_contains($policy->policyname, 'service_role')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Fix RLS for a specific table
     */
    private function fixTableRLS(string $table, array $rlsStatus): void
    {
        // Strategy 1: If RLS is not enabled, simply disable it to ensure unrestricted access
        if (!$rlsStatus['rls_enabled']) {
            Log::info("RLS not enabled on '{$table}', ensuring it stays disabled");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            return;
        }

        // Strategy 2: If RLS is enabled, create service role policy for full access
        Log::info("RLS is enabled on '{$table}', creating service role policy");
        
        // First, drop existing service role policy if it exists
        if ($rlsStatus['has_service_role_policy']) {
            $this->removeServiceRolePolicies($table);
        }

        // Create comprehensive service role policy
        $policyName = "service_role_all_access_{$table}";
        
        DB::statement("
            CREATE POLICY {$policyName} ON {$table}
            FOR ALL
            TO service_role
            USING (true)
            WITH CHECK (true)
        ");

        Log::info("Created service role policy '{$policyName}' on table '{$table}'");

        // Also create authenticated role policy for backward compatibility
        $authPolicyName = "authenticated_all_access_{$table}";
        
        try {
            DB::statement("
                CREATE POLICY {$authPolicyName} ON {$table}
                FOR ALL
                TO authenticated
                USING (true)
                WITH CHECK (true)
            ");
            Log::info("Created authenticated role policy '{$authPolicyName}' on table '{$table}'");
        } catch (\Exception $e) {
            // If authenticated policy fails, it's not critical
            Log::warning("Could not create authenticated policy on '{$table}': " . $e->getMessage());
        }

        // Verify access after policy creation
        $this->verifyTableAccess($table);
    }

    /**
     * Remove service role policies from a table
     */
    private function removeServiceRolePolicies(string $table): void
    {
        // Get all policies for this table that contain 'service_role' or 'authenticated'
        $policies = DB::select("
            SELECT policyname
            FROM pg_policies
            WHERE tablename = ?
            AND (policyname LIKE '%service_role%' OR policyname LIKE '%authenticated%')
        ", [$table]);

        foreach ($policies as $policy) {
            try {
                DB::statement("DROP POLICY IF EXISTS {$policy->policyname} ON {$table}");
                Log::info("Dropped policy '{$policy->policyname}' from table '{$table}'");
            } catch (\Exception $e) {
                Log::warning("Could not drop policy '{$policy->policyname}': " . $e->getMessage());
            }
        }
    }

    /**
     * Verify table access after policy changes
     */
    private function verifyTableAccess(string $table): void
    {
        try {
            // Try a simple SELECT to verify access
            DB::select("SELECT 1 FROM {$table} LIMIT 1");
            Log::info("Verified access to table '{$table}'");
        } catch (\Exception $e) {
            Log::warning("Access verification failed for table '{$table}': " . $e->getMessage());
            // Try disabling RLS as fallback
            try {
                DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
                Log::info("Disabled RLS on '{$table}' as fallback");
            } catch (\Exception $fallbackError) {
                Log::error("Fallback RLS disable failed for '{$table}': " . $fallbackError->getMessage());
            }
        }
    }
};
