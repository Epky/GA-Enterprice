<?php

namespace Tests\Unit;

use App\Models\QueryResult;
use Exception;
use PHPUnit\Framework\TestCase;

class QueryResultTest extends TestCase
{
    public function test_successful_query_result_creation()
    {
        $data = ['test' => 'data'];
        $executionTime = 150.5;
        $retryCount = 1;
        $metadata = ['query_id' => 'test_123'];

        $result = QueryResult::success($data, $executionTime, $retryCount, $metadata);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals($data, $result->data);
        $this->assertEquals($executionTime, $result->getExecutionTimeMs());
        $this->assertEquals($retryCount, $result->retryCount);
        $this->assertEquals($metadata, $result->metadata);
        $this->assertTrue($result->hadRetries());
        $this->assertEquals('normal', $result->getPerformanceClass());
    }

    public function test_failed_query_result_creation()
    {
        $error = new Exception('Test error', 500);
        $executionTime = 2500.0;
        $retryCount = 3;

        $result = QueryResult::failure($error, $executionTime, $retryCount);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertEquals($error, $result->error);
        $this->assertEquals('Test error', $result->getErrorMessage());
        $this->assertEquals($executionTime, $result->getExecutionTimeMs());
        $this->assertEquals($retryCount, $result->retryCount);
        $this->assertEquals('very_slow', $result->getPerformanceClass());
    }

    public function test_performance_classification()
    {
        $fastResult = QueryResult::success([], 50.0);
        $normalResult = QueryResult::success([], 300.0);
        $slowResult = QueryResult::success([], 750.0);
        $verySlowResult = QueryResult::success([], 1500.0);

        $this->assertEquals('fast', $fastResult->getPerformanceClass());
        $this->assertEquals('normal', $normalResult->getPerformanceClass());
        $this->assertEquals('slow', $slowResult->getPerformanceClass());
        $this->assertEquals('very_slow', $verySlowResult->getPerformanceClass());
    }

    public function test_execution_time_conversion()
    {
        $result = QueryResult::success([], 1500.0);

        $this->assertEquals(1500.0, $result->getExecutionTimeMs());
        $this->assertEquals(1.5, $result->getExecutionTimeSeconds());
    }

    public function test_error_details()
    {
        $error = new Exception('Database error', 1001);
        $result = QueryResult::failure($error);

        $errorDetails = $result->getErrorDetails();

        $this->assertIsArray($errorDetails);
        $this->assertEquals('Database error', $errorDetails['message']);
        $this->assertEquals(1001, $errorDetails['code']);
        $this->assertEquals('Exception', $errorDetails['class']);
        $this->assertArrayHasKey('file', $errorDetails);
        $this->assertArrayHasKey('line', $errorDetails);
    }

    public function test_performance_limits()
    {
        $fastResult = QueryResult::success([], 500.0);
        $slowResult = QueryResult::success([], 1500.0);

        $this->assertTrue($fastResult->isWithinPerformanceLimits());
        $this->assertFalse($slowResult->isWithinPerformanceLimits());
        $this->assertTrue($slowResult->isWithinPerformanceLimits(2000.0));
    }

    public function test_performance_metrics()
    {
        $result = QueryResult::success(['data'], 750.0, 2);
        $metrics = $result->getPerformanceMetrics();

        $this->assertIsArray($metrics);
        $this->assertEquals(750.0, $metrics['execution_time_ms']);
        $this->assertEquals(0.75, $metrics['execution_time_seconds']);
        $this->assertEquals('slow', $metrics['performance_class']);
        $this->assertEquals(2, $metrics['retry_count']);
        $this->assertTrue($metrics['had_retries']);
        $this->assertTrue($metrics['success']);
        $this->assertTrue($metrics['within_limits']);
    }

    public function test_summary_creation()
    {
        $result = QueryResult::success(['test'], 250.5, 1);
        $summary = $result->getSummary();

        $this->assertIsArray($summary);
        $this->assertTrue($summary['success']);
        $this->assertEquals(250.5, $summary['execution_time_ms']);
        $this->assertEquals('normal', $summary['performance_class']);
        $this->assertEquals(1, $summary['retry_count']);
        $this->assertNull($summary['error_summary']);
    }

    public function test_failed_result_summary()
    {
        $error = new Exception('Test failure');
        $result = QueryResult::failure($error, 100.0, 0);
        $summary = $result->getSummary();

        $this->assertFalse($summary['success']);
        $this->assertIsArray($summary['error_summary']);
        $this->assertEquals('Test failure', $summary['error_summary']['message']);
        $this->assertEquals('Exception', $summary['error_summary']['class']);
    }

    public function test_to_array_conversion()
    {
        $data = ['test' => 'value'];
        $result = QueryResult::success($data, 300.0, 1, ['query_id' => 'test']);
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertEquals(300.0, $array['execution_time_ms']);
        $this->assertEquals(0.3, $array['execution_time_seconds']);
        $this->assertEquals(1, $array['retry_count']);
        $this->assertTrue($array['had_retries']);
        $this->assertEquals('normal', $array['performance_class']);
        $this->assertNull($array['error_message']);
        $this->assertEquals(['query_id' => 'test'], $array['metadata']);
    }
}