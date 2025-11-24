<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifySystemTablesRLS extends Command
{
    protected $signature = 'verify:system-tables-rls';
    protected $description = 'Verify RLS status of Laravel system tables';

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

    public function handle()
    {
        $this->info('=== Verifying System Tables RLS Status ===');
        $this->newLine();

        $results = [];

        foreach ($this->systemTables as $table) {
            $status = $this->checkTableStatus($table);
            $results[] = $status;
            
            $this->displayTableStatus($table, $status);
        }

        $this->newLine();
        $this->displaySummary($results);

        return 0;
    }

    private function checkTableStatus(string $table): array
    {
        // Check if table exists
        $existsResult = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            )
        ", [$table]);
        
        $exists = $existsResult[0]->exists ?? false;

        if (!$exists) {
            return [
                'exists' => false,
                'rls_enabled' => null,
                'policies_count' => 0,
                'accessible' => false,
                'status' => 'missing'
            ];
        }

        // Check RLS status (only in public schema)
        $rlsResult = DB::select("
            SELECT c.relrowsecurity as enabled
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE c.relname = ? AND n.nspname = 'public'
        ", [$table]);
        
        $rlsEnabled = $rlsResult[0]->enabled ?? false;

        // Count policies (only in public schema)
        $policiesResult = DB::select("
            SELECT COUNT(*) as count
            FROM pg_policies
            WHERE tablename = ? AND schemaname = 'public'
        ", [$table]);
        
        $policiesCount = $policiesResult[0]->count ?? 0;

        // Test access
        $accessible = false;
        try {
            DB::select("SELECT 1 FROM {$table} LIMIT 1");
            $accessible = true;
        } catch (\Exception $e) {
            $accessible = false;
        }

        // Determine status
        $status = 'unknown';
        if (!$rlsEnabled && $accessible) {
            $status = 'ok';
        } elseif ($rlsEnabled && $accessible && $policiesCount > 0) {
            $status = 'ok_with_policies';
        } elseif ($rlsEnabled && !$accessible) {
            $status = 'blocked';
        } elseif ($rlsEnabled && $policiesCount == 0) {
            $status = 'rls_no_policies';
        }

        return [
            'exists' => true,
            'rls_enabled' => $rlsEnabled,
            'policies_count' => $policiesCount,
            'accessible' => $accessible,
            'status' => $status
        ];
    }

    private function displayTableStatus(string $table, array $status): void
    {
        $tableName = str_pad($table, 25);
        
        if (!$status['exists']) {
            $this->line("  {$tableName} <fg=yellow>MISSING</>");
            return;
        }

        $rlsStatus = $status['rls_enabled'] ? '<fg=red>ENABLED</>' : '<fg=green>DISABLED</>';
        $policiesCount = $status['policies_count'];
        $accessible = $status['accessible'] ? '<fg=green>YES</>' : '<fg=red>NO</>';

        switch ($status['status']) {
            case 'ok':
                $this->line("  {$tableName} RLS: {$rlsStatus} | Policies: {$policiesCount} | Access: {$accessible} <fg=green>✓ OK</>");
                break;
            case 'ok_with_policies':
                $this->line("  {$tableName} RLS: {$rlsStatus} | Policies: {$policiesCount} | Access: {$accessible} <fg=green>✓ OK (with policies)</>");
                break;
            case 'blocked':
                $this->line("  {$tableName} RLS: {$rlsStatus} | Policies: {$policiesCount} | Access: {$accessible} <fg=red>✗ BLOCKED</>");
                break;
            case 'rls_no_policies':
                $this->line("  {$tableName} RLS: {$rlsStatus} | Policies: {$policiesCount} | Access: {$accessible} <fg=yellow>⚠ RLS enabled but no policies</>");
                break;
            default:
                $this->line("  {$tableName} RLS: {$rlsStatus} | Policies: {$policiesCount} | Access: {$accessible} <fg=yellow>? UNKNOWN</>");
        }
    }

    private function displaySummary(array $results): void
    {
        $total = count($results);
        $missing = count(array_filter($results, fn($r) => !$r['exists']));
        $ok = count(array_filter($results, fn($r) => in_array($r['status'], ['ok', 'ok_with_policies'])));
        $blocked = count(array_filter($results, fn($r) => $r['status'] === 'blocked'));
        $issues = count(array_filter($results, fn($r) => !in_array($r['status'], ['ok', 'ok_with_policies']) && $r['exists']));

        $this->info('=== Summary ===');
        $this->line("  Total system tables: {$total}");
        $this->line("  Missing: {$missing}");
        $this->line("  OK: <fg=green>{$ok}</>");
        $this->line("  Blocked: <fg=red>{$blocked}</>");
        $this->line("  Other issues: <fg=yellow>{$issues}</>");

        if ($blocked > 0 || $issues > 0) {
            $this->newLine();
            $this->warn('⚠ Some tables have RLS issues. Run the comprehensive fix migration.');
        } else {
            $this->newLine();
            $this->info('✓ All system tables are properly configured!');
        }
    }
}
