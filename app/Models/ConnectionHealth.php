<?php

namespace App\Models;

class ConnectionHealth
{
    public bool $isConnected;
    public bool $preparedStatementsValid;
    public ?float $lastQueryTime;
    public array $recentErrors;
    public int $retryCount;
    public ?\DateTime $lastHealthCheck;
    
    public function __construct(array $data = [])
    {
        $this->isConnected = $data['is_connected'] ?? false;
        $this->preparedStatementsValid = $data['prepared_statements_valid'] ?? false;
        $this->lastQueryTime = $data['last_query_time'] ?? null;
        $this->recentErrors = $data['recent_errors'] ?? [];
        $this->retryCount = $data['retry_count'] ?? 0;
        $this->lastHealthCheck = $data['last_health_check'] ?? null;
    }
    
    /**
     * Check if connection is healthy
     */
    public function isHealthy(): bool
    {
        return $this->isConnected && $this->preparedStatementsValid;
    }
    
    /**
     * Check if connection has recent errors
     */
    public function hasRecentErrors(): bool
    {
        return !empty($this->recentErrors);
    }
    
    /**
     * Get the most recent error
     */
    public function getLastError(): ?string
    {
        return empty($this->recentErrors) ? null : end($this->recentErrors);
    }
    
    /**
     * Check if health check is stale
     */
    public function isStale(int $maxAgeSeconds = 300): bool
    {
        if (!$this->lastHealthCheck) {
            return true;
        }
        
        return $this->lastHealthCheck->getTimestamp() < (time() - $maxAgeSeconds);
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'is_connected' => $this->isConnected,
            'prepared_statements_valid' => $this->preparedStatementsValid,
            'last_query_time' => $this->lastQueryTime,
            'recent_errors' => $this->recentErrors,
            'retry_count' => $this->retryCount,
            'last_health_check' => $this->lastHealthCheck?->format('Y-m-d H:i:s'),
            'is_healthy' => $this->isHealthy(),
            'has_recent_errors' => $this->hasRecentErrors(),
            'is_stale' => $this->isStale()
        ];
    }
    
    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}