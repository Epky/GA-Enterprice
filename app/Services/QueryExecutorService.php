<?php

namespace App\Services;

use App\Models\QueryResult;
use Exception;
use Illuminate\Support\Facades\Log;
use PDOException;

class QueryExecutorService
{
    private DatabaseConnectionManager $connectionManager;
    private array $retryDelays = [100, 200, 400]; // milliseconds
    private int $maxRetries = 3;
    private array $performanceMetrics = [];

    public function __construct(DatabaseConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Execute a query with automatic retry for prepared statement errors
     *
     * @param callable $query The query to execute
     * @param array $context Additional context for logging
     * @return QueryResult Query result with execution details
     */
    public function executeQuery(callable $query, array $context = []): QueryResult
    {
        $startTime = microtime(true);
        $lastException = null;
        $queryId = uniqid('query_', true);
        
        // Log query start
        $this->logQueryStart($queryId, $context);
        
        for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
            try {
                // Ensure connection is healthy before executing
                $this->connectionManager->ensureConnection();
                
                // Execute the query
                $result = $query();
                
                // Calculate execution time
                $executionTime = (microtime(true) - $startTime) * 1000;
                
                // Create successful result
                $queryResult = QueryResult::success(
                    data: $result,
                    executionTime: $executionTime,
                    retryCount: $attempt,
                    metadata: array_merge($context, [
                        'query_id' => $queryId,
                        'connection_health' => $this->connectionManager->getConnectionHealth()->toArray()
                    ])
                );
                
                // Log successful execution
                $this->logQuerySuccess($queryResult, $context);
                
                // Track performance metrics
                $this->trackPerformanceMetrics($queryResult);
                
                // Check for performance alerts
                $this->logPerformanceAlert($queryResult);
                
                // Log detailed context for debugging if needed
                if (config('app.debug') || $queryResult->getPerformanceClass() === 'very_slow') {
                    $this->logQueryContext($queryId, $context, [
                        'performance_metrics' => $queryResult->getPerformanceMetrics(),
                        'connection_health' => $this->connectionManager->getConnectionHealth()->toArray()
                    ]);
                }
                
                return $queryResult;
                
            } catch (Exception $e) {
                $lastException = $e;
                
                // Log attempt failure
                $this->logQueryAttemptFailure($e, $attempt + 1, $queryId, $context);
                
                // Check if this is a prepared statement error
                if ($this->isPreparedStatementError($e)) {
                    // Reset connection for prepared statement errors
                    $this->connectionManager->resetConnection();
                    
                    // Apply exponential backoff if not the last attempt
                    if ($attempt < $this->maxRetries - 1) {
                        $delayMs = $this->retryDelays[$attempt] ?? 400;
                        usleep($delayMs * 1000); // Convert to microseconds
                        continue;
                    }
                } else {
                    // For non-prepared statement errors, don't retry
                    break;
                }
            }
        }
        
        // All retries exhausted or non-retryable error
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        $queryResult = QueryResult::failure(
            error: $lastException,
            executionTime: $executionTime,
            retryCount: $attempt,
            metadata: array_merge($context, [
                'query_id' => $queryId,
                'max_retries' => $this->maxRetries
            ])
        );
        
        // Log final failure
        $this->logQueryFailure($queryResult, $context);
        
        return $queryResult;
    }

    /**
     * Execute a query and throw exception on failure (backward compatibility)
     *
     * @param callable $query The query to execute
     * @param array $context Additional context for logging
     * @return mixed Query result data
     * @throws Exception
     */
    public function executeQueryOrFail(callable $query, array $context = [])
    {
        $result = $this->executeQuery($query, $context);
        
        if ($result->isFailure()) {
            throw $result->error;
        }
        
        return $result->data;
    }

    /**
     * Execute query with fallback option
     *
     * @param callable $primary Primary query to execute
     * @param callable $fallback Fallback query if primary fails
     * @param array $context Additional context for logging
     * @return QueryResult Query result
     */
    public function executeWithFallback(callable $primary, callable $fallback, array $context = []): QueryResult
    {
        $primaryResult = $this->executeQuery($primary, array_merge($context, ['query_type' => 'primary']));
        
        if ($primaryResult->isSuccess()) {
            return $primaryResult;
        }
        
        // Log fallback attempt
        Log::channel('database')->warning('Primary query failed, executing fallback', [
            'primary_error' => $primaryResult->getErrorMessage(),
            'primary_execution_time' => $primaryResult->getExecutionTimeMs(),
            'primary_retry_count' => $primaryResult->retryCount,
            'primary_performance_class' => $primaryResult->getPerformanceClass(),
            'context' => $context,
            'fallback_triggered' => true,
            'timestamp' => now()->toISOString()
        ]);
        
        $fallbackResult = $this->executeQuery($fallback, array_merge($context, ['query_type' => 'fallback']));
        
        if ($fallbackResult->isFailure()) {
            Log::channel('database')->error('Both primary and fallback queries failed', [
                'primary_error' => $primaryResult->getErrorMessage(),
                'primary_execution_time' => $primaryResult->getExecutionTimeMs(),
                'primary_retry_count' => $primaryResult->retryCount,
                'fallback_error' => $fallbackResult->getErrorMessage(),
                'fallback_execution_time' => $fallbackResult->getExecutionTimeMs(),
                'fallback_retry_count' => $fallbackResult->retryCount,
                'total_execution_time' => $primaryResult->getExecutionTimeMs() + $fallbackResult->getExecutionTimeMs(),
                'context' => $context,
                'alert_type' => 'complete_query_failure',
                'severity' => 'critical',
                'timestamp' => now()->toISOString()
            ]);
        } else {
            Log::channel('database')->info('Fallback query succeeded', [
                'primary_error' => $primaryResult->getErrorMessage(),
                'fallback_execution_time' => $fallbackResult->getExecutionTimeMs(),
                'fallback_retry_count' => $fallbackResult->retryCount,
                'total_execution_time' => $primaryResult->getExecutionTimeMs() + $fallbackResult->getExecutionTimeMs(),
                'context' => $context,
                'recovery_successful' => true,
                'timestamp' => now()->toISOString()
            ]);
        }
        
        return $fallbackResult;
    }

    /**
     * Handle prepared statement specific errors
     *
     * @param Exception $e The exception to handle
     * @return bool True if handled, false otherwise
     */
    public function handlePreparedStatementError(Exception $e): bool
    {
        if ($this->isPreparedStatementError($e)) {
            Log::channel('database')->error('Prepared statement error handled', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'sqlstate' => $this->getSQLState($e),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'connection_reset' => true,
                'timestamp' => now()->toISOString()
            ]);
            
            // Reset connection to clear prepared statements
            $this->connectionManager->resetConnection();
            
            return true;
        }
        
        return false;
    }

    /**
     * Check if the exception is a prepared statement error
     *
     * @param Exception $e The exception to check
     * @return bool True if it's a prepared statement error
     */
    private function isPreparedStatementError(Exception $e): bool
    {
        $message = $e->getMessage();
        $sqlState = $this->getSQLState($e);
        
        // Check for SQLSTATE[26000] or specific prepared statement error messages
        return $sqlState === '26000' || 
               str_contains($message, 'prepared statement') ||
               str_contains($message, 'does not exist') ||
               str_contains($message, 'SQLSTATE[26000]');
    }

    /**
     * Extract SQLSTATE from exception
     *
     * @param Exception $e The exception
     * @return string|null The SQLSTATE code
     */
    private function getSQLState(Exception $e): ?string
    {
        if ($e instanceof PDOException && $e->getCode()) {
            return $e->getCode();
        }
        
        // Try to extract SQLSTATE from message
        if (preg_match('/SQLSTATE\[([^\]]+)\]/', $e->getMessage(), $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Log query start
     *
     * @param string $queryId Unique query identifier
     * @param array $context Query context
     */
    private function logQueryStart(string $queryId, array $context): void
    {
        Log::channel('database')->info('Query execution started', [
            'query_id' => $queryId,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'connection_name' => config('database.default'),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Log successful query execution
     *
     * @param QueryResult $result Query result
     * @param array $context Query context
     */
    private function logQuerySuccess(QueryResult $result, array $context): void
    {
        $logData = [
            'query_id' => $result->metadata['query_id'] ?? 'unknown',
            'execution_time_ms' => $result->getExecutionTimeMs(),
            'execution_time_seconds' => $result->getExecutionTimeSeconds(),
            'performance_class' => $result->getPerformanceClass(),
            'retry_count' => $result->retryCount,
            'had_retries' => $result->hadRetries(),
            'context' => $context,
            'memory_usage_after' => memory_get_usage(true),
            'connection_health' => $result->metadata['connection_health'] ?? null,
            'timestamp' => now()->toISOString()
        ];

        // Log to database channel
        Log::channel('database')->info('Query executed successfully', $logData);

        // Log performance metrics to dedicated performance channel
        Log::channel('query_performance')->info('Query performance metrics', [
            'query_id' => $result->metadata['query_id'] ?? 'unknown',
            'execution_time_ms' => $result->getExecutionTimeMs(),
            'performance_class' => $result->getPerformanceClass(),
            'retry_count' => $result->retryCount,
            'context_type' => $context['query_type'] ?? 'unknown',
            'timestamp' => now()->toISOString()
        ]);

        // Log slow queries as warnings
        if ($result->getPerformanceClass() === 'very_slow') {
            Log::channel('database')->warning('Slow query detected', array_merge($logData, [
                'alert_type' => 'slow_query',
                'threshold_exceeded' => '1000ms'
            ]));
        }

        // Log queries that required retries
        if ($result->hadRetries()) {
            Log::channel('database')->warning('Query required retries', array_merge($logData, [
                'alert_type' => 'retry_required',
                'retry_details' => 'Query succeeded after ' . $result->retryCount . ' retries'
            ]));
        }
    }

    /**
     * Log query attempt failure
     *
     * @param Exception $exception The exception that occurred
     * @param int $attempt Current attempt number
     * @param string $queryId Query identifier
     * @param array $context Query context
     */
    private function logQueryAttemptFailure(Exception $exception, int $attempt, string $queryId, array $context): void
    {
        $logLevel = $this->isPreparedStatementError($exception) ? 'warning' : 'error';
        
        $logData = [
            'query_id' => $queryId,
            'attempt' => $attempt,
            'max_retries' => $this->maxRetries,
            'error' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'sqlstate' => $this->getSQLState($exception),
            'is_prepared_statement_error' => $this->isPreparedStatementError($exception),
            'context' => $context,
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace_summary' => $this->getTraceSummary($exception),
            'memory_usage' => memory_get_usage(true),
            'timestamp' => now()->toISOString()
        ];

        Log::channel('database')->log($logLevel, 'Query attempt failed', $logData);

        // Log prepared statement errors with additional context
        if ($this->isPreparedStatementError($exception)) {
            Log::channel('database')->warning('Prepared statement error detected', [
                'query_id' => $queryId,
                'attempt' => $attempt,
                'error_type' => 'prepared_statement',
                'sqlstate' => $this->getSQLState($exception),
                'will_retry' => $attempt < $this->maxRetries,
                'connection_reset_triggered' => true,
                'context' => $context
            ]);
        }
    }

    /**
     * Log final query failure
     *
     * @param QueryResult $result Failed query result
     * @param array $context Query context
     */
    private function logQueryFailure(QueryResult $result, array $context): void
    {
        $logData = [
            'query_id' => $result->metadata['query_id'] ?? 'unknown',
            'error' => $result->getErrorMessage(),
            'execution_time_ms' => $result->getExecutionTimeMs(),
            'execution_time_seconds' => $result->getExecutionTimeSeconds(),
            'retry_count' => $result->retryCount,
            'max_retries' => $result->metadata['max_retries'] ?? $this->maxRetries,
            'context' => $context,
            'result_data' => $result->toArray(),
            'alert_type' => 'query_failure',
            'severity' => 'critical',
            'memory_usage' => memory_get_usage(true),
            'timestamp' => now()->toISOString()
        ];

        Log::channel('database')->error('Query execution failed after all retries', $logData);

        // Also log to main channel for critical errors
        Log::error('Critical database query failure', [
            'query_id' => $result->metadata['query_id'] ?? 'unknown',
            'error' => $result->getErrorMessage(),
            'retry_count' => $result->retryCount,
            'execution_time_ms' => $result->getExecutionTimeMs(),
            'context_summary' => $context['query_type'] ?? 'unknown'
        ]);

        // Log performance impact of failed queries
        Log::channel('query_performance')->error('Failed query performance impact', [
            'query_id' => $result->metadata['query_id'] ?? 'unknown',
            'execution_time_ms' => $result->getExecutionTimeMs(),
            'retry_count' => $result->retryCount,
            'performance_class' => $result->getPerformanceClass(),
            'context_type' => $context['query_type'] ?? 'unknown',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Track performance metrics
     *
     * @param QueryResult $result Query result to track
     */
    private function trackPerformanceMetrics(QueryResult $result): void
    {
        $this->performanceMetrics[] = [
            'execution_time' => $result->getExecutionTimeMs(),
            'retry_count' => $result->retryCount,
            'performance_class' => $result->getPerformanceClass(),
            'timestamp' => now()->timestamp
        ];

        // Keep only last 100 metrics to prevent memory issues
        if (count($this->performanceMetrics) > 100) {
            $this->performanceMetrics = array_slice($this->performanceMetrics, -100);
        }
    }

    /**
     * Get performance statistics
     *
     * @return array Performance statistics
     */
    public function getPerformanceStatistics(): array
    {
        if (empty($this->performanceMetrics)) {
            return [
                'total_queries' => 0,
                'average_execution_time' => 0,
                'total_retries' => 0,
                'performance_distribution' => []
            ];
        }

        $totalQueries = count($this->performanceMetrics);
        $totalExecutionTime = array_sum(array_column($this->performanceMetrics, 'execution_time'));
        $totalRetries = array_sum(array_column($this->performanceMetrics, 'retry_count'));
        
        $performanceClasses = array_count_values(array_column($this->performanceMetrics, 'performance_class'));

        return [
            'total_queries' => $totalQueries,
            'average_execution_time' => $totalExecutionTime / $totalQueries,
            'total_retries' => $totalRetries,
            'retry_rate' => ($totalRetries / $totalQueries) * 100,
            'performance_distribution' => $performanceClasses
        ];
    }

    /**
     * Clear performance metrics
     */
    public function clearPerformanceMetrics(): void
    {
        $this->performanceMetrics = [];
    }

    /**
     * Get a summary of the exception trace for logging
     *
     * @param Exception $exception The exception
     * @return array Trace summary
     */
    private function getTraceSummary(Exception $exception): array
    {
        $trace = $exception->getTrace();
        $summary = [];
        
        // Get first 3 trace entries for context
        for ($i = 0; $i < min(3, count($trace)); $i++) {
            $entry = $trace[$i];
            $summary[] = [
                'file' => basename($entry['file'] ?? 'unknown'),
                'line' => $entry['line'] ?? 'unknown',
                'function' => $entry['function'] ?? 'unknown',
                'class' => $entry['class'] ?? null
            ];
        }
        
        return $summary;
    }

    /**
     * Log query context with detailed information
     *
     * @param string $queryId Query identifier
     * @param array $context Query context
     * @param array $additionalData Additional data to log
     */
    public function logQueryContext(string $queryId, array $context, array $additionalData = []): void
    {
        Log::channel('database')->debug('Query context details', [
            'query_id' => $queryId,
            'context' => $context,
            'additional_data' => $additionalData,
            'system_info' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'connection_name' => config('database.default'),
                'php_version' => PHP_VERSION,
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Log performance alert when thresholds are exceeded
     *
     * @param QueryResult $result Query result
     * @param array $thresholds Performance thresholds
     */
    public function logPerformanceAlert(QueryResult $result, array $thresholds = []): void
    {
        $defaultThresholds = [
            'slow_query_ms' => 1000,
            'very_slow_query_ms' => 2000,
            'max_retries' => 2
        ];
        
        $thresholds = array_merge($defaultThresholds, $thresholds);
        
        $alerts = [];
        
        if ($result->getExecutionTimeMs() > $thresholds['very_slow_query_ms']) {
            $alerts[] = 'very_slow_execution';
        } elseif ($result->getExecutionTimeMs() > $thresholds['slow_query_ms']) {
            $alerts[] = 'slow_execution';
        }
        
        if ($result->retryCount > $thresholds['max_retries']) {
            $alerts[] = 'excessive_retries';
        }
        
        if (!empty($alerts)) {
            Log::channel('database')->warning('Performance alert triggered', [
                'query_id' => $result->metadata['query_id'] ?? 'unknown',
                'alerts' => $alerts,
                'execution_time_ms' => $result->getExecutionTimeMs(),
                'retry_count' => $result->retryCount,
                'thresholds' => $thresholds,
                'performance_class' => $result->getPerformanceClass(),
                'timestamp' => now()->toISOString()
            ]);
        }
    }
}