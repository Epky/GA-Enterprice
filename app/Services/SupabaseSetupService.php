<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Exception;

class SupabaseSetupService
{
    /**
     * Validate Supabase configuration
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        $warnings = [];
        
        // Check required environment variables
        $requiredVars = [
            'SUPABASE_URL',
            'SUPABASE_DB_HOST',
            'SUPABASE_DB_DATABASE',
            'SUPABASE_DB_USERNAME',
            'SUPABASE_DB_PASSWORD'
        ];
        
        foreach ($requiredVars as $var) {
            if (empty(env($var))) {
                $errors[] = "Missing required environment variable: {$var}";
            }
        }
        
        // Check if PostgreSQL extension is available
        if (!extension_loaded('pdo_pgsql')) {
            $errors[] = 'PostgreSQL PDO extension (pdo_pgsql) is not installed';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Test database connection
     */
    public function testConnection(): array
    {
        try {
            $connection = DB::connection('supabase');
            
            // Test basic connectivity
            $result = $connection->select('SELECT NOW() as current_time, version() as version');
            
            if (empty($result)) {
                throw new Exception('No response from database');
            }
            
            // Test permissions
            $canCreateTable = $this->testCreatePermissions($connection);
            
            return [
                'success' => true,
                'message' => 'Successfully connected to Supabase',
                'details' => [
                    'version' => $result[0]->version,
                    'current_time' => $result[0]->current_time,
                    'can_create_tables' => $canCreateTable
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to Supabase',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test table creation permissions
     */
    private function testCreatePermissions($connection): bool
    {
        try {
            $testTable = 'supabase_connection_test_' . time();
            
            $connection->statement("CREATE TABLE {$testTable} (id SERIAL PRIMARY KEY, test_column VARCHAR(50))");
            $connection->statement("DROP TABLE {$testTable}");
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Switch default database connection to Supabase
     */
    public function switchToSupabase(): void
    {
        Config::set('database.default', 'supabase');
        DB::purge('supabase');
        DB::reconnect('supabase');
    }
    
    /**
     * Get connection information
     */
    public function getConnectionInfo(): array
    {
        return [
            'current_default' => config('database.default'),
            'supabase_config' => config('database.connections.supabase'),
            'environment_vars' => [
                'SUPABASE_URL' => env('SUPABASE_URL') ? 'Set' : 'Not set',
                'SUPABASE_DB_HOST' => env('SUPABASE_DB_HOST') ? 'Set' : 'Not set',
                'SUPABASE_DB_DATABASE' => env('SUPABASE_DB_DATABASE') ? 'Set' : 'Not set',
                'SUPABASE_DB_USERNAME' => env('SUPABASE_DB_USERNAME') ? 'Set' : 'Not set',
                'SUPABASE_DB_PASSWORD' => env('SUPABASE_DB_PASSWORD') ? 'Set' : 'Not set',
            ]
        ];
    }
}