<?php

namespace App\Models;

use Exception;

class QueryResult
{
    public bool $success;
    public mixed $data;
    public ?Exception $error;
    public float $executionTime;
    public int $retryCount;
    public array $metadata;

    public function __construct(
        bool $success = false,
        mixed $data = null,
        ?Exception $error = null,
        float $executionTime = 0.0,
        int $retryCount = 0,
        array $metadata = []
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
        $this->executionTime = $executionTime;
        $this->retryCount = $retryCount;
        $this->metadata = $metadata;
    }

    /**
     * Create a successful query result
     *
     * @param mixed $data The query result data
     * @param float $executionTime Execution time in milliseconds
     * @param int $retryCount Number of retries performed
     * @param array $metadata Additional metadata
     * @return QueryResult
     */
    public static function success(
        mixed $data,
        float $executionTime = 0.0,
        int $retryCount = 0,
        array $metadata = []
    ): QueryResult {
        return new self(
            success: true,
            data: $data,
            executionTime: $executionTime,
            retryCount: $retryCount,
            metadata: $metadata
        );
    }

    /**
     * Create a failed query result
     *
     * @param Exception $error The error that occurred
     * @param float $executionTime Execution time in milliseconds
     * @param int $retryCount Number of retries performed
     * @param array $metadata Additional metadata
     * @return QueryResult
     */
    public static function failure(
        Exception $error,
        float $executionTime = 0.0,
        int $retryCount = 0,
        array $metadata = []
    ): QueryResult {
        return new self(
            success: false,
            error: $error,
            executionTime: $executionTime,
            retryCount: $retryCount,
            metadata: $metadata
        );
    }

    /**
     * Check if the query was successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the query failed
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get the error message if query failed
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->error?->getMessage();
    }

    /**
     * Get execution time in milliseconds
     *
     * @return float
     */
    public function getExecutionTimeMs(): float
    {
        return $this->executionTime;
    }

    /**
     * Get execution time in seconds
     *
     * @return float
     */
    public function getExecutionTimeSeconds(): float
    {
        return $this->executionTime / 1000;
    }

    /**
     * Check if query required retries
     *
     * @return bool
     */
    public function hadRetries(): bool
    {
        return $this->retryCount > 0;
    }

    /**
     * Get performance classification
     *
     * @return string
     */
    public function getPerformanceClass(): string
    {
        if ($this->executionTime < 100) {
            return 'fast';
        } elseif ($this->executionTime < 500) {
            return 'normal';
        } elseif ($this->executionTime < 1000) {
            return 'slow';
        } else {
            return 'very_slow';
        }
    }

    /**
     * Convert to array for logging
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'execution_time_ms' => $this->executionTime,
            'execution_time_seconds' => $this->getExecutionTimeSeconds(),
            'retry_count' => $this->retryCount,
            'had_retries' => $this->hadRetries(),
            'performance_class' => $this->getPerformanceClass(),
            'error_message' => $this->getErrorMessage(),
            'error_code' => $this->error?->getCode(),
            'error_class' => $this->error ? get_class($this->error) : null,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get detailed error information for logging
     *
     * @return array|null
     */
    public function getErrorDetails(): ?array
    {
        if (!$this->error) {
            return null;
        }

        return [
            'message' => $this->error->getMessage(),
            'code' => $this->error->getCode(),
            'class' => get_class($this->error),
            'file' => $this->error->getFile(),
            'line' => $this->error->getLine(),
            'trace_count' => count($this->error->getTrace())
        ];
    }

    /**
     * Check if query execution was within acceptable performance limits
     *
     * @param float $maxExecutionTimeMs Maximum acceptable execution time in milliseconds
     * @return bool
     */
    public function isWithinPerformanceLimits(float $maxExecutionTimeMs = 1000): bool
    {
        return $this->executionTime <= $maxExecutionTimeMs;
    }

    /**
     * Get performance metrics for monitoring
     *
     * @return array
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'execution_time_ms' => $this->executionTime,
            'execution_time_seconds' => $this->getExecutionTimeSeconds(),
            'performance_class' => $this->getPerformanceClass(),
            'retry_count' => $this->retryCount,
            'had_retries' => $this->hadRetries(),
            'success' => $this->success,
            'within_limits' => $this->isWithinPerformanceLimits(),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Create a summary for logging purposes
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->success,
            'execution_time_ms' => round($this->executionTime, 2),
            'performance_class' => $this->getPerformanceClass(),
            'retry_count' => $this->retryCount,
            'error_summary' => $this->success ? null : [
                'message' => $this->getErrorMessage(),
                'class' => $this->error ? get_class($this->error) : null
            ]
        ];
    }
}