<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * RLS Policy Manager Service
 * 
 * Manages Row Level Security policies for Laravel system tables in Supabase
 */
class RLSPolicyManager
{
    /**
     * Laravel system tables that need RLS policies
     */
    private const SYSTEM_TABLES = [
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
     * Service role name for Supabase
     */
    private string $serviceRole;

    /**
     * Database connection name
     */
    private string $connection;

    public function __construct()
    {
        $this->serviceRole = config('database.rls_management.service_role', 'service_role');
        $this->connection = config('database.default', 'supabase');
    }

    /**
     * Detect existing RLS policies on system tables
     * 
     * @return array Array of table names with their policy status
     */
    public function detectSystemTablePolicies(): array
    {
        $policies = [];
        
        foreach (self::SYSTEM_TABLES as $table) {
            $policies[$table] = $this->validatePolicyConfiguration($table);
        }
        
        return $policies;
    }

    /**
     * Create service role policies for specified tables
     * 
     * @param array $tables Array of table names
     * @return bool Success status
     */
    public function createServiceRolePolicies(array $tables): bool
    {
        try {
            DB::connection($this->connection)->transaction(function () use ($tables) {
                foreach ($tables as $table) {
                    $this->createServiceRolePolicy($table);
                }
            });
            
            Log::info('Successfully created service role policies', ['tables' => $tables]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to create service role policies', [
                'tables' => $tables,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update existing policies for specified tables
     * 
     * @param array $tables Array of table names
     * @return bool Success status
     */
    public function updateExistingPolicies(array $tables): bool
    {
        try {
            DB::connection($this->connection)->transaction(function () use ($tables) {
                foreach ($tables as $table) {
                    // Drop existing restrictive policies first
                    $this->dropRestrictivePolicies($table);
                    // Create new service role policy
                    $this->createServiceRolePolicy($table);
                }
            });
            
            Log::info('Successfully updated existing policies', ['tables' => $tables]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to update existing policies', [
                'tables' => $tables,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Rollback policy changes for specified tables
     * 
     * @param array $tables Array of table names
     * @return bool Success status
     */
    public function rollbackPolicyChanges(array $tables): bool
    {
        try {
            DB::connection($this->connection)->transaction(function () use ($tables) {
                foreach ($tables as $table) {
                    $this->dropServiceRolePolicy($table);
                }
            });
            
            Log::info('Successfully rolled back policy changes', ['tables' => $tables]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to rollback policy changes', [
                'tables' => $tables,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate policy configuration for a specific table
     * 
     * @param string $table Table name
     * @return PolicyStatus Policy status object
     */
    public function validatePolicyConfiguration(string $table): PolicyStatus
    {
        try {
            // Check if table exists
            $tableExists = $this->tableExists($table);
            if (!$tableExists) {
                return new PolicyStatus(
                    hasRLS: false,
                    hasServiceRolePolicy: false,
                    existingPolicies: [],
                    needsUpdate: false,
                    errorMessage: "Table '{$table}' does not exist"
                );
            }

            // Check if RLS is enabled
            $rlsEnabled = $this->isRLSEnabled($table);
            
            // Get existing policies
            $existingPolicies = $this->getExistingPolicies($table);
            
            // Check if service role policy exists
            $hasServiceRolePolicy = $this->hasServiceRolePolicy($table);
            
            // Determine if update is needed
            $needsUpdate = $rlsEnabled && !$hasServiceRolePolicy;
            
            return new PolicyStatus(
                hasRLS: $rlsEnabled,
                hasServiceRolePolicy: $hasServiceRolePolicy,
                existingPolicies: $existingPolicies,
                needsUpdate: $needsUpdate,
                errorMessage: null
            );
            
        } catch (Exception $e) {
            return new PolicyStatus(
                hasRLS: false,
                hasServiceRolePolicy: false,
                existingPolicies: [],
                needsUpdate: false,
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Check if table exists in database
     * 
     * @param string $table Table name
     * @return bool
     */
    private function tableExists(string $table): bool
    {
        $result = DB::connection($this->connection)
            ->select("SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            )", [$table]);
            
        return $result[0]->exists ?? false;
    }

    /**
     * Check if RLS is enabled on table
     * 
     * @param string $table Table name
     * @return bool
     */
    private function isRLSEnabled(string $table): bool
    {
        if (!$this->isPostgreSQLConnection()) {
            return false;
        }

        $result = DB::connection($this->connection)
            ->select("SELECT relrowsecurity FROM pg_class WHERE relname = ?", [$table]);
            
        return $result[0]->relrowsecurity ?? false;
    }

    /**
     * Get existing policies for table
     * 
     * @param string $table Table name
     * @return array
     */
    private function getExistingPolicies(string $table): array
    {
        if (!$this->isPostgreSQLConnection()) {
            return [];
        }

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
    }

    /**
     * Check if service role policy exists for table
     * 
     * @param string $table Table name
     * @return bool
     */
    private function hasServiceRolePolicy(string $table): bool
    {
        $result = DB::connection($this->connection)
            ->select("
                SELECT COUNT(*) as count 
                FROM pg_policies 
                WHERE tablename = ? 
                AND ? = ANY(roles)
            ", [$table, $this->serviceRole]);
            
        return ($result[0]->count ?? 0) > 0;
    }

    /**
     * Create service role policy for table
     * 
     * @param string $table Table name
     * @return void
     */
    private function createServiceRolePolicy(string $table): void
    {
        $policyName = "service_role_full_access_{$table}";
        
        // Drop existing policy if it exists
        DB::connection($this->connection)
            ->statement("DROP POLICY IF EXISTS {$policyName} ON {$table}");
        
        // Create new policy
        DB::connection($this->connection)
            ->statement("
                CREATE POLICY {$policyName} ON {$table}
                FOR ALL TO {$this->serviceRole}
                USING (true)
                WITH CHECK (true)
            ");
            
        Log::info("Created service role policy for table", [
            'table' => $table,
            'policy' => $policyName
        ]);
    }

    /**
     * Drop restrictive policies that might block service role access
     * 
     * @param string $table Table name
     * @return void
     */
    private function dropRestrictivePolicies(string $table): void
    {
        $policies = $this->getExistingPolicies($table);
        
        foreach ($policies as $policy) {
            // Skip if policy already allows service role
            if (in_array($this->serviceRole, $policy['roles'])) {
                continue;
            }
            
            // Drop restrictive policies
            DB::connection($this->connection)
                ->statement("DROP POLICY IF EXISTS {$policy['name']} ON {$table}");
                
            Log::info("Dropped restrictive policy", [
                'table' => $table,
                'policy' => $policy['name']
            ]);
        }
    }

    /**
     * Drop service role policy for table
     * 
     * @param string $table Table name
     * @return void
     */
    private function dropServiceRolePolicy(string $table): void
    {
        $policyName = "service_role_full_access_{$table}";
        
        DB::connection($this->connection)
            ->statement("DROP POLICY IF EXISTS {$policyName} ON {$table}");
            
        Log::info("Dropped service role policy for table", [
            'table' => $table,
            'policy' => $policyName
        ]);
    }

    /**
     * Get all system tables that need RLS policies
     * 
     * @return array
     */
    public function getSystemTables(): array
    {
        return self::SYSTEM_TABLES;
    }

    /**
     * Check if the current connection is PostgreSQL
     * 
     * @return bool
     */
    private function isPostgreSQLConnection(): bool
    {
        $driver = DB::connection($this->connection)->getDriverName();
        return in_array($driver, ['pgsql', 'postgresql']);
    }

    /**
     * Create comprehensive RLS policies for users table
     * 
     * @return bool Success status
     */
    public function createUserTablePolicies(): bool
    {
        try {
            // Check if we're using PostgreSQL (required for RLS)
            if (!$this->isPostgreSQLConnection()) {
                Log::warning('Cannot create RLS policies: PostgreSQL connection required');
                return false;
            }

            DB::connection($this->connection)->transaction(function () {
                // Enable RLS on users table
                $this->enableRLSOnUsersTable();
                
                // Create service role full access policy
                $this->createServiceRoleUserPolicy();
                
                // Create user self-access policy
                $this->createUserSelfAccessPolicy();
                
                // Create admin management policy
                $this->createAdminManagementPolicy();
            });
            
            Log::info('Successfully created users table RLS policies');
            return true;
        } catch (Exception $e) {
            Log::error('Failed to create users table RLS policies', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate users table policy configuration
     * 
     * @return UserTablePolicyStatus Policy status for users table
     */
    public function validateUserTablePolicies(): UserTablePolicyStatus
    {
        try {
            // Check if we're using PostgreSQL (required for RLS)
            if (!$this->isPostgreSQLConnection()) {
                return new UserTablePolicyStatus(
                    tableExists: false,
                    hasRLS: false,
                    hasServiceRolePolicy: false,
                    hasUserSelfAccessPolicy: false,
                    hasAdminManagementPolicy: false,
                    existingPolicies: [],
                    needsUpdate: false,
                    errorMessage: "RLS policies are only supported on PostgreSQL databases"
                );
            }

            // Check if users table exists
            $tableExists = $this->tableExists('users');
            if (!$tableExists) {
                return new UserTablePolicyStatus(
                    tableExists: false,
                    hasRLS: false,
                    hasServiceRolePolicy: false,
                    hasUserSelfAccessPolicy: false,
                    hasAdminManagementPolicy: false,
                    existingPolicies: [],
                    needsUpdate: false,
                    errorMessage: "Users table does not exist"
                );
            }

            // Check if RLS is enabled
            $rlsEnabled = $this->isRLSEnabled('users');
            
            // Get existing policies
            $existingPolicies = $this->getExistingPolicies('users');
            
            // Check for specific policy types
            $hasServiceRolePolicy = $this->hasUserServiceRolePolicy();
            $hasUserSelfAccessPolicy = $this->hasUserSelfAccessPolicy();
            $hasAdminManagementPolicy = $this->hasAdminManagementPolicy();
            
            // Determine if update is needed
            $needsUpdate = $tableExists && (!$rlsEnabled || !$hasServiceRolePolicy || !$hasUserSelfAccessPolicy || !$hasAdminManagementPolicy);
            
            return new UserTablePolicyStatus(
                tableExists: $tableExists,
                hasRLS: $rlsEnabled,
                hasServiceRolePolicy: $hasServiceRolePolicy,
                hasUserSelfAccessPolicy: $hasUserSelfAccessPolicy,
                hasAdminManagementPolicy: $hasAdminManagementPolicy,
                existingPolicies: $existingPolicies,
                needsUpdate: $needsUpdate,
                errorMessage: null
            );
            
        } catch (Exception $e) {
            return new UserTablePolicyStatus(
                tableExists: false,
                hasRLS: false,
                hasServiceRolePolicy: false,
                hasUserSelfAccessPolicy: false,
                hasAdminManagementPolicy: false,
                existingPolicies: [],
                needsUpdate: false,
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Test users table access with different roles
     * 
     * @return array Test results
     */
    public function testUserTableAccess(): array
    {
        $results = [];
        
        try {
            // Check if we're using PostgreSQL (required for RLS)
            if (!$this->isPostgreSQLConnection()) {
                $results['error'] = 'RLS testing is only supported on PostgreSQL databases';
                return $results;
            }

            // Test service role access (should work for all operations)
            $results['service_role_access'] = $this->testServiceRoleAccess();
            
            // Test basic table operations
            $results['table_operations'] = $this->testBasicTableOperations();
            
            // Test RLS policy enforcement
            $results['rls_enforcement'] = $this->testRLSPolicyEnforcement();
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Enable RLS on users table
     * 
     * @return void
     */
    private function enableRLSOnUsersTable(): void
    {
        DB::connection($this->connection)
            ->statement("ALTER TABLE users ENABLE ROW LEVEL SECURITY");
            
        Log::info("Enabled RLS on users table");
    }

    /**
     * Create service role policy for users table
     * 
     * @return void
     */
    private function createServiceRoleUserPolicy(): void
    {
        $policyName = "service_role_users_all";
        
        // Drop existing policy if it exists
        DB::connection($this->connection)
            ->statement("DROP POLICY IF EXISTS {$policyName} ON users");
        
        // Create new policy
        DB::connection($this->connection)
            ->statement("
                CREATE POLICY {$policyName} ON users
                FOR ALL TO {$this->serviceRole}
                USING (true)
                WITH CHECK (true)
            ");
            
        Log::info("Created service role policy for users table", [
            'policy' => $policyName
        ]);
    }

    /**
     * Create user self-access policy
     * 
     * @return void
     */
    private function createUserSelfAccessPolicy(): void
    {
        $policyName = "users_own_records";
        
        // Drop existing policy if it exists
        DB::connection($this->connection)
            ->statement("DROP POLICY IF EXISTS {$policyName} ON users");
        
        // Create new policy - users can read and update their own records
        DB::connection($this->connection)
            ->statement("
                CREATE POLICY {$policyName} ON users
                FOR ALL TO authenticated
                USING (auth.uid()::text = id::text)
                WITH CHECK (auth.uid()::text = id::text)
            ");
            
        Log::info("Created user self-access policy for users table", [
            'policy' => $policyName
        ]);
    }

    /**
     * Create admin management policy
     * 
     * @return void
     */
    private function createAdminManagementPolicy(): void
    {
        $policyName = "admin_users_all";
        
        // Drop existing policy if it exists
        DB::connection($this->connection)
            ->statement("DROP POLICY IF EXISTS {$policyName} ON users");
        
        // Create new policy - admin users can manage all records
        DB::connection($this->connection)
            ->statement("
                CREATE POLICY {$policyName} ON users
                FOR ALL TO authenticated
                USING (
                    EXISTS (
                        SELECT 1 FROM users u 
                        WHERE u.id::text = auth.uid()::text 
                        AND u.role = 'admin'
                        AND u.is_active = true
                    )
                )
                WITH CHECK (
                    EXISTS (
                        SELECT 1 FROM users u 
                        WHERE u.id::text = auth.uid()::text 
                        AND u.role = 'admin'
                        AND u.is_active = true
                    )
                )
            ");
            
        Log::info("Created admin management policy for users table", [
            'policy' => $policyName
        ]);
    }

    /**
     * Check if service role policy exists for users table
     * 
     * @return bool
     */
    private function hasUserServiceRolePolicy(): bool
    {
        if (!$this->isPostgreSQLConnection()) {
            return false;
        }

        $result = DB::connection($this->connection)
            ->select("
                SELECT COUNT(*) as count 
                FROM pg_policies 
                WHERE tablename = 'users' 
                AND policyname = 'service_role_users_all'
            ");
            
        return ($result[0]->count ?? 0) > 0;
    }

    /**
     * Check if user self-access policy exists
     * 
     * @return bool
     */
    private function hasUserSelfAccessPolicy(): bool
    {
        if (!$this->isPostgreSQLConnection()) {
            return false;
        }

        $result = DB::connection($this->connection)
            ->select("
                SELECT COUNT(*) as count 
                FROM pg_policies 
                WHERE tablename = 'users' 
                AND policyname = 'users_own_records'
            ");
            
        return ($result[0]->count ?? 0) > 0;
    }

    /**
     * Check if admin management policy exists
     * 
     * @return bool
     */
    private function hasAdminManagementPolicy(): bool
    {
        if (!$this->isPostgreSQLConnection()) {
            return false;
        }

        $result = DB::connection($this->connection)
            ->select("
                SELECT COUNT(*) as count 
                FROM pg_policies 
                WHERE tablename = 'users' 
                AND policyname = 'admin_users_all'
            ");
            
        return ($result[0]->count ?? 0) > 0;
    }

    /**
     * Test service role access to users table
     * 
     * @return array Test results
     */
    private function testServiceRoleAccess(): array
    {
        try {
            // Test basic select operation
            $count = DB::connection($this->connection)
                ->table('users')
                ->count();
                
            return [
                'success' => true,
                'message' => 'Service role can access users table',
                'user_count' => $count
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Service role access failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test basic table operations
     * 
     * @return array Test results
     */
    private function testBasicTableOperations(): array
    {
        try {
            // Test table structure
            $columns = DB::connection($this->connection)
                ->select("
                    SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns 
                    WHERE table_name = 'users' 
                    AND table_schema = 'public'
                    ORDER BY ordinal_position
                ");
                
            $requiredColumns = ['id', 'name', 'email', 'password', 'role', 'is_active'];
            $existingColumns = array_column($columns, 'column_name');
            $missingColumns = array_diff($requiredColumns, $existingColumns);
            
            return [
                'success' => empty($missingColumns),
                'message' => empty($missingColumns) ? 'All required columns exist' : 'Missing columns: ' . implode(', ', $missingColumns),
                'columns' => $columns,
                'missing_columns' => $missingColumns
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Table structure test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test RLS policy enforcement
     * 
     * @return array Test results
     */
    private function testRLSPolicyEnforcement(): array
    {
        try {
            // Check if RLS is enabled
            $rlsEnabled = $this->isRLSEnabled('users');
            
            // Get policy count
            $policyCount = DB::connection($this->connection)
                ->select("SELECT COUNT(*) as count FROM pg_policies WHERE tablename = 'users'");
                
            return [
                'success' => $rlsEnabled && ($policyCount[0]->count ?? 0) >= 3,
                'message' => $rlsEnabled ? 'RLS is enabled with policies' : 'RLS is not properly configured',
                'rls_enabled' => $rlsEnabled,
                'policy_count' => $policyCount[0]->count ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'RLS enforcement test failed: ' . $e->getMessage()
            ];
        }
    }
}

/**
 * Policy Status Data Class
 */
class PolicyStatus
{
    public function __construct(
        public bool $hasRLS,
        public bool $hasServiceRolePolicy,
        public array $existingPolicies,
        public bool $needsUpdate,
        public ?string $errorMessage = null
    ) {}
}

/**
 * User Table Policy Status Data Class
 */
class UserTablePolicyStatus
{
    public function __construct(
        public bool $tableExists,
        public bool $hasRLS,
        public bool $hasServiceRolePolicy,
        public bool $hasUserSelfAccessPolicy,
        public bool $hasAdminManagementPolicy,
        public array $existingPolicies,
        public bool $needsUpdate,
        public ?string $errorMessage = null
    ) {}
}