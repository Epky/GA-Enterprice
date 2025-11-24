<?php

namespace App\Console\Commands;

use App\Services\DatabaseConnectionManager;
use Illuminate\Console\Command;

class TestDatabaseConnection extends Command
{
    protected $signature = 'db:test-connection {connection?}';
    protected $description = 'Test database connection and prepared statement handling';

    public function handle(DatabaseConnectionManager $connectionManager): int
    {
        $connectionName = $this->argument('connection') ?? config('database.default');
        
        $this->info("Testing database connection: {$connectionName}");
        $this->newLine();
        
        // Perform health check
        $this->info('Performing health check...');
        $health = $connectionManager->performHealthCheck($connectionName);
        
        // Display health status
        $this->displayHealthStatus($health);
        
        // Test prepared statements
        $this->info('Testing prepared statement functionality...');
        $this->testPreparedStatements($connectionManager, $connectionName);
        
        // Display diagnostics
        $this->info('Getting diagnostics...');
        $diagnostics = $connectionManager->getDiagnostics($connectionName);
        $this->displayDiagnostics($diagnostics);
        
        return Command::SUCCESS;
    }
    
    private function displayHealthStatus($health): void
    {
        $healthArray = $health->toArray();
        
        $this->table(
            ['Property', 'Value'],
            [
                ['Connected', $healthArray['is_connected'] ? '✓ Yes' : '✗ No'],
                ['Prepared Statements Valid', $healthArray['prepared_statements_valid'] ? '✓ Yes' : '✗ No'],
                ['Healthy', $healthArray['is_healthy'] ? '✓ Yes' : '✗ No'],
                ['Has Recent Errors', $healthArray['has_recent_errors'] ? '⚠ Yes' : '✓ No'],
                ['Data Stale', $healthArray['is_stale'] ? '⚠ Yes' : '✓ No'],
                ['Last Health Check', $healthArray['last_health_check'] ?? 'Never'],
            ]
        );
        
        if ($healthArray['has_recent_errors']) {
            $this->warn('Recent errors:');
            foreach ($health->recentErrors as $error) {
                $this->line("  - {$error}");
            }
        }
        
        $this->newLine();
    }
    
    private function testPreparedStatements(DatabaseConnectionManager $connectionManager, string $connectionName): void
    {
        try {
            // Track a test prepared statement
            $sql = 'SELECT ? as test_value, ? as test_name';
            $statementId = $connectionManager->trackPreparedStatement($sql, $connectionName);
            
            $this->info("✓ Tracked prepared statement: {$statementId}");
            
            // Get prepared statement stats
            $stats = $connectionManager->getPreparedStatementStats($connectionName);
            $this->info("✓ Prepared statement stats retrieved");
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Statements', $stats['total_statements']],
                    ['Valid Statements', $stats['valid_statements']],
                    ['Invalid Statements', $stats['invalid_statements']],
                ]
            );
            
        } catch (\Exception $e) {
            $this->error("✗ Prepared statement test failed: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function displayDiagnostics(array $diagnostics): void
    {
        $this->info('Connection Configuration:');
        $config = $diagnostics['configuration'];
        $this->table(
            ['Setting', 'Value'],
            [
                ['Driver', $config['driver'] ?? 'N/A'],
                ['Host', $config['host'] ?? 'N/A'],
                ['Port', $config['port'] ?? 'N/A'],
                ['Database', $config['database'] ?? 'N/A'],
                ['SSL Mode', $config['sslmode'] ?? 'N/A'],
            ]
        );
        
        if (!empty($diagnostics['recommendations'])) {
            $this->warn('Recommendations:');
            foreach ($diagnostics['recommendations'] as $recommendation) {
                $this->line("  - {$recommendation}");
            }
        } else {
            $this->info('✓ No recommendations - connection looks good!');
        }
    }
}