<?php

namespace Tests\Feature;

use App\Models\QueryResult;
use App\Services\DatabaseConnectionManager;
use App\Services\QueryExecutorService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class QueryResultLoggingTest extends TestCase
{
    public function test_query_result_logging_configuration()
    {
        // Test that logging channels are properly configured
        $channels = config('logging.channels');
        
        $this->assertArrayHasKey('database', $channels);
        $this->assertArrayHasKey('query_performance', $channels);
        
        $this->assertEquals('daily', $channels['database']['driver']);
        $this->assertEquals('daily', $channels['query_performance']['driver']);
    }

    public function test_query_result_performance_metrics()
    {
        $result = QueryResult::success(['test' => 'data'], 250.5, 1);
        
        $metrics = $result->getPerformanceMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertEquals(250.5, $metrics['execution_time_ms']);
        $this->assertEquals(0.2505, $metrics['execution_time_seconds']);
        $this->assertEquals('normal', $metrics['performance_class']);
        $this->assertEquals(1, $metrics['retry_count']);
        $this->assertTrue($metrics['had_retries']);
        $this->assertTrue($metrics['success']);
        $this->assertTrue($metrics['within_limits']);
    }

    public function test_query_result_error_details()
    {
        $error = new Exception('Test database error', 1001);
        $result = QueryResult::failure($error, 500.0, 2);
        
        $errorDetails = $result->getErrorDetails();
        
        $this->assertIsArray($errorDetails);
        $this->assertEquals('Test database error', $errorDetails['message']);
        $this->assertEquals(1001, $errorDetails['code']);
        $this->assertEquals('Exception', $errorDetails['class']);
        $this->assertArrayHasKey('file', $errorDetails);
        $this->assertArrayHasKey('line', $errorDetails);
        $this->assertArrayHasKey('trace_count', $errorDetails);
    }

    public function test_query_result_summary_creation()
    {
        // Test successful result summary
        $successResult = QueryResult::success(['data'], 150.0, 0);
        $successSummary = $successResult->getSummary();
        
        $this->assertTrue($successSummary['success']);
        $this->assertEquals(150.0, $successSummary['execution_time_ms']);
        $this->assertEquals('normal', $successSummary['performance_class']);
        $this->assertEquals(0, $successSummary['retry_count']);
        $this->assertNull($successSummary['error_summary']);
        
        // Test failed result summary
        $error = new Exception('Test failure');
        $failureResult = QueryResult::failure($error, 100.0, 1);
        $failureSummary = $failureResult->getSummary();
        
        $this->assertFalse($failureSummary['success']);
        $this->assertEquals(100.0, $failureSummary['execution_time_ms']);
        $this->assertEquals(1, $failureSummary['retry_count']);
        $this->assertIsArray($failureSummary['error_summary']);
        $this->assertEquals('Test failure', $failureSummary['error_summary']['message']);
    }

    public function test_query_result_performance_classification()
    {
        $fastResult = QueryResult::success([], 50.0);
        $normalResult = QueryResult::success([], 300.0);
        $slowResult = QueryResult::success([], 750.0);
        $verySlowResult = QueryResult::success([], 1500.0);

        $this->assertEquals('fast', $fastResult->getPerformanceClass());
        $this->assertEquals('normal', $normalResult->getPerformanceClass());
        $this->assertEquals('slow', $slowResult->getPerformanceClass());
        $this->assertEquals('very_slow', $verySlowResult->getPerformanceClass());
        
        // Test performance limits
        $this->assertTrue($fastResult->isWithinPerformanceLimits());
        $this->assertTrue($normalResult->isWithinPerformanceLimits());
        $this->assertTrue($slowResult->isWithinPerformanceLimits());
        $this->assertFalse($verySlowResult->isWithinPerformanceLimits());
        
        // Test custom performance limits
        $this->assertTrue($verySlowResult->isWithinPerformanceLimits(2000.0));
        $this->assertFalse($verySlowResult->isWithinPerformanceLimits(1000.0));
    }

    public function test_query_result_to_array_conversion()
    {
        $data = ['test' => 'value'];
        $metadata = ['query_id' => 'test_123', 'context' => 'test'];
        $result = QueryResult::success($data, 300.0, 1, $metadata);
        
        $array = $result->toArray();
        
        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertEquals(300.0, $array['execution_time_ms']);
        $this->assertEquals(0.3, $array['execution_time_seconds']);
        $this->assertEquals(1, $array['retry_count']);
        $this->assertTrue($array['had_retries']);
        $this->assertEquals('normal', $array['performance_class']);
        $this->assertNull($array['error_message']);
        $this->assertNull($array['error_code']);
        $this->assertNull($array['error_class']);
        $this->assertEquals($metadata, $array['metadata']);
        $this->assertArrayHasKey('timestamp', $array);
    }

    public function test_logging_channels_exist()
    {
        // Test that we can get the logging channels without errors
        $databaseChannel = Log::channel('database');
        $performanceChannel = Log::channel('query_performance');
        
        $this->assertNotNull($databaseChannel);
        $this->assertNotNull($performanceChannel);
    }
}