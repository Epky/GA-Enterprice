<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * System Table Detector Service
 * 
 * Detects and analyzes Laravel system tables in the database
 */
class SystemTableDetector
{
    /**
     * Required Laravel system tables
     */
    private const REQUIRED_SYSTEM_TABLES = [
        'cache' => [
            'description' => 'Laravel cache storage table',
            'required_for' => 'Cache functionality'
        ],
        'cache_locks' => [
            'description' => 'Laravel cache locks table',
            'required_for' => 'Cache locking mechanism'
        ],
        'jobs' => [
            'description' => 'Laravel queue jobs table',
            'required_for' => 'Queue job processing'
        ],
        'job_batches' => [
            'description' => 'Laravel job batches table',
            'required_for' => 'Batch job processing'
        ],
        'failed_jobs' => [
            'description' => 'Laravel failed jobs table',
            'required_for' => 'Failed job tracking'
        ],
        'sessions' => [
            'description' => 'Laravel sessions table',
            'required_for' => 'Session management'
        ],
        'password_reset_tokens' => [
            'description' => 'Laravel password reset tokens table',
            'required_for' => 'Password reset functionality'
        ],
        'personal_access_tokens' => [
            'description' => 'Laravel Sanctum personal access tokens table',
            'required_for' => 'API authentication'
        ],
        'migrations' => [
            'description' => 'Laravel migrations tracking table',
            'required_for' => 'Migration system'
        ]
    ];

    /**
     * Database connection name
     */
    private string $connection;

    public function __construct()
    {
        $this->connection = config('database.default', 'supabase');
    }

    /**
     * Get all existing system tables in the database
     * 
     * @return array Array of existing system table names with their details
     */
    public function getExistingSystemTables(): array
    {
        $existingTables = [];
        
        foreach (self::REQUIRED_SYSTEM_TABLES as $tableName => $tableInfo) {
            if ($this->tableExists($tableName)) {
                $existingTables[$tableName] = array_merge($tableInfo, [
                    'exists' => true,
                    'rls_status' => $this->checkTableRLSStatus($tableName)
                ]);
            }
        }
        
        return $existingTables;
    }

    /**
     * Get all required system tables (whether they exist or not)
     * 
     * @return array Array of all required system tables
     */
    public function getRequiredSystemTables(): array
    {
        $requiredTables = [];
        
        foreach (self::REQUIRED_SYSTEM_TABLES as $tableName => $tableInfo) {
            $requiredTables[$tableName] = array_merge($tableInfo, [
                'exists' => $this->tableExists($tableName),
                'rls_status' => $this->tableExists($tableName) 
                    ? $this->checkTableRLSStatus($tableName) 
                    : null
            ]);
        }
        
        return $requiredTables;
    }

    /**
     * Check RLS status for a specific table
     * 
     * @param string $table Table name
     * @return RLSStatus RLS status object
     */
    public function checkTableRLSStatus(string $table): RLSStatus
    {
        try {
            $exists = $this->tableExists($table);
            
            if (!$exists) {
                return new RLSStatus(
                    exists: false,
                    rlsEnabled: false,
                    policies: [],
                    accessible: false,
                    issue: "Table does not exist"
                );
            }

            $rlsEnabled = $this->isRLSEnabled($table);
            $policies = $this->getTablePolicies($table);
            $accessible = $this->testTableAccess($table);
            
            $issue = null;
            if ($rlsEnabled && !$accessible) {
                $issue = "RLS is enabled but table is not accessible";
            } elseif ($rlsEnabled && empty($policies)) {
                $issue = "RLS is enabled but no policies exist";
            }
            
            return new RLSStatus(
                exists: true,
                rlsEnabled: $rlsEnabled,
                policies: $policies,
                accessible: $accessible,
                issue: $issue
            );
            
        } catch (Exception $e) {
            return new RLSStatus(
                exists: false,
                rlsEnabled: false,
                policies: [],
                accessible: false,
                issue: "Error checking RLS status: " . $e->getMessage()
            );
        }
    }

    /**
     * Identify tables that have RLS issues and need fixing
     * 
     * @return array Array of problematic tables with their issues
     */
    public function identifyProblematicTables(): array
    {
        $problematicTables = [];
        
        foreach (self::REQUIRED_SYSTEM_TABLES as $tableName => $tableInfo) {
            $rlsStatus = $this->checkTableRLSStatus($tableName);
            
            // Table doesn't exist
            if (!$rlsStatus->exists) {
                $problematicTables[$tableName] = [
                    'issue_type' => 'missing_table',
                    'description' => 'Table does not exist and needs to be created',
                    'rls_status' => $rlsStatus,
                    'table_info' => $tableInfo
                ];
                continue;
            }
            
            // RLS is enabled but table is not accessible
            if ($rlsStatus->rlsEnabled && !$rlsStatus->accessible) {
                $problematicTables[$tableName] = [
                    'issue_type' => 'rls_blocking_access',
                    'description' => 'RLS policies are blocking access to system table',
                    'rls_status' => $rlsStatus,
                    'table_info' => $tableInfo
                ];
                continue;
            }
            
            // RLS is enabled but no service role policy exists
            if ($rlsStatus->rlsEnabled && !$this->hasServiceRolePolicy($tableName)) {
                $problematicTables[$tableName] = [
                    'issue_type' => 'missing_service_role_policy',
                    'description' => 'RLS is enabled but no service role policy exists',
                    'rls_status' => $rlsStatus,
                    'table_info' => $tableInfo
                ];
            }
        }
        
        return $problematicTables;
    }

    /**
     * Get comprehensive system table analysis
     * 
     * @return array Detailed analysis of all system tables
     */
    public function getSystemTableAnalysis(): array
    {
        return [
            'existing_tables' => $this->getExistingSystemTables(),
            'required_tables' => $this->getRequiredSystemTables(),
            'problematic_tables' => $this->identifyProblematicTables(),
            'summary' => $this->generateAnalysisSummary()
        ];
    }

    /**
     * Generate analysis summary
     * 
     * @return array Summary statistics
     */
    private function generateAnalysisSummary(): array
    {
        $requiredTables = $this->getRequiredSystemTables();
        $problematicTables = $this->identifyProblematicTables();
        
        $totalRequired = count($requiredTables);
        $existing = count(array_filter($requiredTables, fn($table) => $table['exists']));
        $problematic = count($problematicTables);
        $healthy = $existing - $problematic;
        
        return [
            'total_required' => $totalRequired,
            'existing' => $existing,
            'missing' => $totalRequired - $existing,
            'problematic' => $problematic,
            'healthy' => $healthy,
            'needs_attention' => $problematic > 0
        ];
    }

    /**
     * Check if table exists in database
     * 
     * @param string $table Table name
     * @return bool
     */
    private function tableExists(string $table): bool
    {
        try {
            $result = DB::connection($this->connection)
                ->select("SELECT EXISTS (
                    SELECT FROM information_schema.tables 
                    WHERE table_schema = 'public' 
                    AND table_name = ?
                )", [$table]);
                
            return $result[0]->exists ?? false;
        } catch (Exception $e) {
            Log::error("Error checking if table exists", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if RLS is enabled on table
     * 
     * @param string $table Table name
     * @return bool
     */
    private function isRLSEnabled(string $table): bool
    {
        try {
            $result = DB::connection($this->connection)
                ->select("SELECT relrowsecurity FROM pg_class WHERE relname = ?", [$table]);
                
            return $result[0]->relrowsecurity ?? false;
        } catch (Exception $e) {
            Log::error("Error checking RLS status", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get policies for a specific table
     * 
     * @param string $table Table name
     * @return array
     */
    private function getTablePolicies(string $table): array
    {
        try {
            $policies = DB::connection($this->connection)
                ->select("
                    SELECT policyname, roles, cmd, qual, with_check 
                    FROM pg_policies 
                    WHERE tablename = ?
                ", [$table]);
                
            return array_map(function ($policy) {
                return [
                    'name' => $policy->policyname,
                    'roles' => $policy->roles ? explode(',', trim($policy->roles, '{}')) : [],
                    'command' => $policy->cmd,
                    'using' => $policy->qual,
                    'with_check' => $policy->with_check
                ];
            }, $policies);
        } catch (Exception $e) {
            Log::error("Error getting table policies", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Test if table is accessible (basic read/write test)
     * 
     * @param string $table Table name
     * @return bool
     */
    private function testTableAccess(string $table): bool
    {
        try {
            // Try a simple SELECT query to test read access
            DB::connection($this->connection)
                ->select("SELECT 1 FROM {$table} LIMIT 1");
            return true;
        } catch (Exception $e) {
            // If SELECT fails, table is not accessible
            return false;
        }
    }

    /**
     * Check if service role policy exists for table
     * 
     * @param string $table Table name
     * @return bool
     */
    private function hasServiceRolePolicy(string $table): bool
    {
        try {
            $serviceRole = config('database.rls_management.service_role', 'service_role');
            
            $result = DB::connection($this->connection)
                ->select("
                    SELECT COUNT(*) as count 
                    FROM pg_policies 
                    WHERE tablename = ? 
                    AND ? = ANY(roles)
                ", [$table, $serviceRole]);
                
            return ($result[0]->count ?? 0) > 0;
        } catch (Exception $e) {
            Log::error("Error checking service role policy", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get list of missing system tables
     * 
     * @return array Array of missing table names
     */
    public function getMissingSystemTables(): array
    {
        $missingTables = [];
        
        foreach (self::REQUIRED_SYSTEM_TABLES as $tableName => $tableInfo) {
            if (!$this->tableExists($tableName)) {
                $missingTables[] = $tableName;
            }
        }
        
        return $missingTables;
    }

    /**
     * Get system table information
     * 
     * @param string $table Table name
     * @return array|null Table information or null if not a system table
     */
    public function getSystemTableInfo(string $table): ?array
    {
        return self::REQUIRED_SYSTEM_TABLES[$table] ?? null;
    }
}

/**
 * RLS Status Data Class
 */
class RLSStatus
{
    public function __construct(
        public bool $exists,
        public bool $rlsEnabled,
        public array $policies,
        public bool $accessible,
        public ?string $issue = null
    ) {}
}