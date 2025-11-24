<?php

namespace App\Services;

use App\Models\ConnectionHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Connection;
use PDO;
use PDOException;
use Exception;

class DatabaseConnectionManager
{
    private array $connectionHealth = [];
    private array $preparedStatements = [];
    private int $maxRetries = 3;
    private array $retryDelays = [100, 200, 400]; // milliseconds
    
    /**
     * Ensure database connection is healthy and ready for queries
     */
    public function ensureConnection(string $connectionName = null): bool
    {
        $connectionName = $connectionName ?? config('database.default');
        
        try {
            $connection = DB::connection($connectionName);
            
            // Check if connection is alive
            if (!$this->isConnectionAlive($connection)) {
                Log::warning("Database connection {$connectionName} is not alive, attempting to reconnect");
                $this->resetConnection($connectionName);
                $connection = DB::connection($connectionName);
            }
            
            // Update connection health status
            $this->updateConnectionHealth($connectionName, true);
            
            return true;
        } catch (Exception $e) {
            Log::error("Failed to ensure database connection {$connectionName}: " . $e->getMessage());
            $this->updateConnectionHealth($connectionName, false, $e);
            return false;
        }
    }
    
    /**
     * Reset database connection and clear prepared statements
     */
    public function resetConnection(string $connectionName = null): void
    {
        $connectionName = $connectionName ?? config('database.default');
        
        try {
            // Clear prepared statements for this connection
            $this->invalidatePreparedStatements($connectionName);
            
            // Disconnect and reconnect
            DB::purge($connectionName);
            
            // Test new connection
            $connection = DB::connection($connectionName);
            $this->testConnection($connection);
            
            Log::info("Successfully reset database connection: {$connectionName}");
            
        } catch (Exception $e) {
            Log::error("Failed to reset database connection {$connectionName}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get connection health status
     */
    public function getConnectionHealth(string $connectionName = null): ConnectionHealth
    {
        $connectionName = $connectionName ?? config('database.default');
        
        $healthData = $this->connectionHealth[$connectionName] ?? [
            'is_connected' => false,
            'prepared_statements_valid' => false,
            'last_query_time' => null,
            'recent_errors' => [],
            'retry_count' => 0,
            'last_health_check' => null
        ];
        
        return ConnectionHealth::fromArray($healthData);
    }
    
    /**
     * Track prepared statement usage
     */
    public function trackPreparedStatement(string $sql, string $connectionName = null): string
    {
        $connectionName = $connectionName ?? config('database.default');
        $statementId = md5($sql . $connectionName);
        
        $this->preparedStatements[$connectionName][$statementId] = [
            'sql' => $sql,
            'created_at' => now(),
            'last_used' => now(),
            'use_count' => ($this->preparedStatements[$connectionName][$statementId]['use_count'] ?? 0) + 1,
            'is_valid' => true
        ];
        
        return $statementId;
    }
    
    /**
     * Check if prepared statements are valid
     */
    public function arePreparedStatementsValid(string $connectionName = null): bool
    {
        $connectionName = $connectionName ?? config('database.default');
        
        if (!isset($this->preparedStatements[$connectionName])) {
            return true; // No statements to validate
        }
        
        foreach ($this->preparedStatements[$connectionName] as $statement) {
            if (!$statement['is_valid']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Invalidate all prepared statements for a connection
     */
    public function invalidatePreparedStatements(string $connectionName = null): void
    {
        $connectionName = $connectionName ?? config('database.default');
        
        if (isset($this->preparedStatements[$connectionName])) {
            foreach ($this->preparedStatements[$connectionName] as &$statement) {
                $statement['is_valid'] = false;
            }
        }
        
        Log::info("Invalidated prepared statements for connection: {$connectionName}");
    }
    
    /**
     * Handle prepared statement error specifically
     */
    public function handlePreparedStatementError(Exception $e, string $connectionName = null): bool
    {
        $connectionName = $connectionName ?? config('database.default');
        
        // Check if this is a prepared statement error
        if ($this->isPreparedStatementError($e)) {
            Log::warning("Detected prepared statement error on {$connectionName}: " . $e->getMessage());
            
            try {
                // Reset connection and invalidate prepared statements
                $this->resetConnection($connectionName);
                return true;
            } catch (Exception $resetException) {
                Log::error("Failed to recover from prepared statement error: " . $resetException->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Configure Supabase-specific connection options
     */
    public function configureSupabaseConnection(string $connectionName = 'supabase'): void
    {
        try {
            $connection = DB::connection($connectionName);
            $pdo = $connection->getPdo();
            
            // Set Supabase-specific PDO options
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);
            $pdo->setAttribute(PDO::ATTR_PERSISTENT, false);
            
            // PostgreSQL specific settings for Supabase
            $pdo->exec("SET statement_timeout = '30s'");
            $pdo->exec("SET lock_timeout = '10s'");
            $pdo->exec("SET idle_in_transaction_session_timeout = '60s'");
            
            Log::info("Configured Supabase connection with optimized settings");
            
        } catch (Exception $e) {
            Log::error("Failed to configure Supabase connection: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Test database connection with a simple query
     */
    private function testConnection(Connection $connection): bool
    {
        try {
            $connection->select('SELECT 1 as test');
            return true;
        } catch (Exception $e) {
            Log::error("Connection test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if connection is alive
     */
    private function isConnectionAlive(Connection $connection): bool
    {
        try {
            $pdo = $connection->getPdo();
            
            // Check PDO connection
            if (!$pdo) {
                return false;
            }
            
            // Test with a simple query
            return $this->testConnection($connection);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Update connection health status
     */
    private function updateConnectionHealth(string $connectionName, bool $isConnected, Exception $error = null): void
    {
        $health = $this->getConnectionHealth($connectionName);
        
        $this->connectionHealth[$connectionName] = [
            'is_connected' => $isConnected,
            'prepared_statements_valid' => $this->arePreparedStatementsValid($connectionName),
            'last_query_time' => microtime(true),
            'recent_errors' => $error ? 
                array_slice(array_merge($health->recentErrors, [$error->getMessage()]), -5) : 
                $health->recentErrors,
            'retry_count' => $health->retryCount,
            'last_health_check' => now()
        ];
    }
    
    /**
     * Check if exception is a prepared statement error
     */
    private function isPreparedStatementError(Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        // Common prepared statement error patterns
        $patterns = [
            'prepared statement',
            'does not exist',
            'sqlstate[26000]',
            'invalid statement name',
            'statement already exists'
        ];
        
        foreach ($patterns as $pattern) {
            if (strpos($message, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get prepared statement statistics
     */
    public function getPreparedStatementStats(string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        
        if (!isset($this->preparedStatements[$connectionName])) {
            return [
                'total_statements' => 0,
                'valid_statements' => 0,
                'invalid_statements' => 0,
                'most_used' => null
            ];
        }
        
        $statements = $this->preparedStatements[$connectionName];
        $total = count($statements);
        $valid = 0;
        $mostUsed = null;
        $maxUseCount = 0;
        
        foreach ($statements as $id => $statement) {
            if ($statement['is_valid']) {
                $valid++;
            }
            
            if ($statement['use_count'] > $maxUseCount) {
                $maxUseCount = $statement['use_count'];
                $mostUsed = [
                    'id' => $id,
                    'sql' => substr($statement['sql'], 0, 100) . '...',
                    'use_count' => $statement['use_count']
                ];
            }
        }
        
        return [
            'total_statements' => $total,
            'valid_statements' => $valid,
            'invalid_statements' => $total - $valid,
            'most_used' => $mostUsed
        ];
    }
    
    /**
     * Clean up old prepared statements
     */
    public function cleanupPreparedStatements(string $connectionName = null, int $maxAge = 3600): void
    {
        $connectionName = $connectionName ?? config('database.default');
        
        if (!isset($this->preparedStatements[$connectionName])) {
            return;
        }
        
        $cutoff = now()->subSeconds($maxAge);
        $cleaned = 0;
        
        foreach ($this->preparedStatements[$connectionName] as $id => $statement) {
            if ($statement['last_used'] < $cutoff) {
                unset($this->preparedStatements[$connectionName][$id]);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            Log::info("Cleaned up {$cleaned} old prepared statements for connection: {$connectionName}");
        }
    }
    
    /**
     * Perform comprehensive connection health check
     */
    public function performHealthCheck(string $connectionName = null): ConnectionHealth
    {
        $connectionName = $connectionName ?? config('database.default');
        
        try {
            // Test basic connectivity
            $isConnected = $this->ensureConnection($connectionName);
            
            if ($isConnected) {
                // Test prepared statement functionality
                $this->testPreparedStatements($connectionName);
                
                // Configure Supabase-specific settings if needed
                if ($connectionName === 'supabase' || strpos($connectionName, 'supabase') !== false) {
                    $this->configureSupabaseConnection($connectionName);
                }
            }
            
            return $this->getConnectionHealth($connectionName);
            
        } catch (Exception $e) {
            Log::error("Health check failed for connection {$connectionName}: " . $e->getMessage());
            $this->updateConnectionHealth($connectionName, false, $e);
            return $this->getConnectionHealth($connectionName);
        }
    }
    
    /**
     * Test prepared statement functionality
     */
    private function testPreparedStatements(string $connectionName): void
    {
        try {
            $connection = DB::connection($connectionName);
            
            // Test a simple prepared statement
            $result = $connection->select('SELECT ? as test_value', [1]);
            
            if (empty($result) || $result[0]->test_value !== 1) {
                throw new Exception('Prepared statement test failed');
            }
            
            Log::debug("Prepared statement test passed for connection: {$connectionName}");
            
        } catch (Exception $e) {
            Log::warning("Prepared statement test failed for connection {$connectionName}: " . $e->getMessage());
            
            // If it's a prepared statement error, handle it
            if ($this->isPreparedStatementError($e)) {
                $this->handlePreparedStatementError($e, $connectionName);
            } else {
                throw $e;
            }
        }
    }
    
    /**
     * Get connection diagnostics
     */
    public function getDiagnostics(string $connectionName = null): array
    {
        $connectionName = $connectionName ?? config('database.default');
        
        $health = $this->getConnectionHealth($connectionName);
        $stats = $this->getPreparedStatementStats($connectionName);
        
        $diagnostics = [
            'connection_name' => $connectionName,
            'health' => $health->toArray(),
            'prepared_statements' => $stats,
            'configuration' => $this->getConnectionConfiguration($connectionName),
            'recommendations' => $this->getRecommendations($health, $stats)
        ];
        
        return $diagnostics;
    }
    
    /**
     * Get connection configuration details
     */
    private function getConnectionConfiguration(string $connectionName): array
    {
        $config = config("database.connections.{$connectionName}", []);
        
        // Remove sensitive information
        unset($config['password']);
        
        return $config;
    }
    
    /**
     * Get recommendations based on health and statistics
     */
    private function getRecommendations(ConnectionHealth $health, array $stats): array
    {
        $recommendations = [];
        
        if (!$health->isHealthy()) {
            $recommendations[] = 'Connection is not healthy - consider resetting connection';
        }
        
        if ($health->hasRecentErrors()) {
            $recommendations[] = 'Recent errors detected - check logs for details';
        }
        
        if ($stats['invalid_statements'] > 0) {
            $recommendations[] = 'Invalid prepared statements detected - connection reset may be needed';
        }
        
        if ($health->isStale()) {
            $recommendations[] = 'Health check data is stale - perform fresh health check';
        }
        
        if ($stats['total_statements'] > 100) {
            $recommendations[] = 'Large number of prepared statements - consider cleanup';
        }
        
        return $recommendations;
    }
    
    /**
     * Force connection refresh with retry logic
     */
    public function forceRefresh(string $connectionName = null): bool
    {
        $connectionName = $connectionName ?? config('database.default');
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $this->resetConnection($connectionName);
                
                if ($this->ensureConnection($connectionName)) {
                    Log::info("Successfully refreshed connection {$connectionName} on attempt {$attempt}");
                    return true;
                }
                
            } catch (Exception $e) {
                Log::warning("Connection refresh attempt {$attempt} failed for {$connectionName}: " . $e->getMessage());
                
                if ($attempt < $this->maxRetries) {
                    // Wait before retry
                    usleep($this->retryDelays[$attempt - 1] * 1000);
                }
            }
        }
        
        Log::error("Failed to refresh connection {$connectionName} after {$this->maxRetries} attempts");
        return false;
    }
}