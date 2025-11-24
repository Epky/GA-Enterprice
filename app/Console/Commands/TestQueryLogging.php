<?php

namespace App\Console\Commands;

use App\Models\QueryResult;
use App\Services\DatabaseConnectionManager;
use App\Services\QueryExecutorService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;

class TestQueryLogging extends Command
{
    protected $signature = 'test:query-logging {--demo : Run demo queries to test logging}';
    protected $description = 'Test query result handling and logging functionality';

    public function handle()
    {
        $this->info('Testing Query Result Handling and Logging');
        $this->newLine();

        if ($this->option('demo')) {
            $this->runDemoQueries();
        } else {
            $this->testQueryResultFeatures();
        }

        return 0;
    }

    private function testQueryResultFeatures()
    {
        $this->info('1. Testing QueryResult model features:');
        
        // Test successful result
        $successResult = QueryResult::success(['count' => 100], 250.5, 1, ['query_type' => 'count']);
        $this->line("   ✓ Success result: {$successResult->getExecutionTimeMs()}ms, {$successResult->getPerformanceClass()} performance");
        
        // Test failed result
        $error = new Exception('Test database error', 1001);
        $failureResult = QueryResult::failure($error, 1500.0, 3);
        $this->line("   ✓ Failure result: {$failureResult->getExecutionTimeMs()}ms, {$failureResult->retryCount} retries");
        
        // Test performance metrics
        $metrics = $successResult->getPerformanceMetrics();
        $this->line("   ✓ Performance metrics: " . json_encode($metrics, JSON_PRETTY_PRINT));
        
        // Test error details
        $errorDetails = $failureResult->getErrorDetails();
        $this->line("   ✓ Error details: " . json_encode($errorDetails, JSON_PRETTY_PRINT));
        
        $this->newLine();
        $this->info('2. Testing logging configuration:');
        
        // Test logging channels
        $channels = config('logging.channels');
        $this->line("   ✓ Database logging channel configured: " . ($channels['database'] ? 'Yes' : 'No'));
        $this->line("   ✓ Query performance channel configured: " . ($channels['query_performance'] ? 'Yes' : 'No'));
        
        $this->newLine();
        $this->info('3. Testing log writing:');
        
        // Test writing to database log
        Log::channel('database')->info('Test database log entry', [
            'query_id' => 'test_' . uniqid(),
            'execution_time_ms' => 150.0,
            'performance_class' => 'normal',
            'test' => true
        ]);
        $this->line("   ✓ Database log entry written");
        
        // Test writing to performance log
        Log::channel('query_performance')->info('Test performance log entry', [
            'query_id' => 'perf_' . uniqid(),
            'execution_time_ms' => 750.0,
            'performance_class' => 'slow',
            'test' => true
        ]);
        $this->line("   ✓ Performance log entry written");
        
        $this->newLine();
        $this->info('All query result handling and logging features tested successfully!');
    }

    private function runDemoQueries()
    {
        $this->info('Running demo queries to test logging in action...');
        $this->newLine();

        try {
            $connectionManager = app(DatabaseConnectionManager::class);
            $queryExecutor = new QueryExecutorService($connectionManager);

            // Demo 1: Successful query
            $this->info('Demo 1: Successful query');
            $result1 = $queryExecutor->executeQuery(function() {
                return DB::select('SELECT 1 as test_value');
            }, ['demo' => 'successful_query', 'query_type' => 'test']);

            $this->line("   Result: " . ($result1->isSuccess() ? 'Success' : 'Failed'));
            $this->line("   Execution time: {$result1->getExecutionTimeMs()}ms");
            $this->line("   Performance class: {$result1->getPerformanceClass()}");

            $this->newLine();

            // Demo 2: Query with artificial delay (slow query)
            $this->info('Demo 2: Slow query simulation');
            $result2 = $queryExecutor->executeQuery(function() {
                usleep(1200000); // 1.2 second delay
                return DB::select('SELECT 2 as slow_value');
            }, ['demo' => 'slow_query', 'query_type' => 'test']);

            $this->line("   Result: " . ($result2->isSuccess() ? 'Success' : 'Failed'));
            $this->line("   Execution time: {$result2->getExecutionTimeMs()}ms");
            $this->line("   Performance class: {$result2->getPerformanceClass()}");

            $this->newLine();

            // Demo 3: Query with fallback
            $this->info('Demo 3: Query with fallback');
            $result3 = $queryExecutor->executeWithFallback(
                function() {
                    throw new Exception('Primary query failed');
                },
                function() {
                    return DB::select('SELECT 3 as fallback_value');
                },
                ['demo' => 'fallback_query', 'query_type' => 'test']
            );

            $this->line("   Result: " . ($result3->isSuccess() ? 'Success' : 'Failed'));
            $this->line("   Execution time: {$result3->getExecutionTimeMs()}ms");
            $this->line("   Used fallback: " . ($result3->metadata['query_type'] === 'fallback' ? 'Yes' : 'No'));

            $this->newLine();

            // Demo 4: Performance statistics
            $this->info('Demo 4: Performance statistics');
            $stats = $queryExecutor->getPerformanceStatistics();
            $this->line("   Total queries: {$stats['total_queries']}");
            $this->line("   Average execution time: " . round($stats['average_execution_time'], 2) . "ms");
            $this->line("   Total retries: {$stats['total_retries']}");
            $this->line("   Performance distribution: " . json_encode($stats['performance_distribution']));

        } catch (Exception $e) {
            $this->error("Demo failed: " . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('Demo completed! Check the logs at:');
        $this->line('   - storage/logs/database.log');
        $this->line('   - storage/logs/query-performance.log');
    }
}