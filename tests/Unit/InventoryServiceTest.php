<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    public function test_inventory_service_can_be_instantiated()
    {
        $this->assertInstanceOf(InventoryService::class, $this->inventoryService);
    }

    public function test_can_get_inventory_stats()
    {
        $stats = $this->inventoryService->getInventoryStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_items', $stats);
        $this->assertArrayHasKey('low_stock_items', $stats);
        $this->assertArrayHasKey('out_of_stock_items', $stats);
        $this->assertArrayHasKey('in_stock_items', $stats);
        $this->assertArrayHasKey('total_value', $stats);
        $this->assertArrayHasKey('low_stock_percentage', $stats);
    }

    public function test_can_get_inventory_alerts()
    {
        $alerts = $this->inventoryService->getInventoryAlerts();
        
        $this->assertIsArray($alerts);
        $this->assertArrayHasKey('critical_stock', $alerts);
        $this->assertArrayHasKey('low_stock', $alerts);
        $this->assertArrayHasKey('out_of_stock', $alerts);
    }

    public function test_can_get_stock_tracking_report()
    {
        $report = $this->inventoryService->getStockTrackingReport();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('movement_statistics', $report);
        $this->assertArrayHasKey('inventory_statistics', $report);
        $this->assertArrayHasKey('alerts', $report);
        $this->assertArrayHasKey('recent_movements', $report);
    }

    public function test_can_validate_stock_levels()
    {
        $validation = $this->inventoryService->validateStockLevels();
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('total_items_checked', $validation);
        $this->assertArrayHasKey('issues_found', $validation);
        $this->assertArrayHasKey('issues', $validation);
        $this->assertArrayHasKey('validation_passed', $validation);
        $this->assertArrayHasKey('checked_at', $validation);
    }

    public function test_can_get_low_stock_alert_dashboard()
    {
        $dashboard = $this->inventoryService->getLowStockAlertDashboard();
        
        $this->assertIsArray($dashboard);
        $this->assertArrayHasKey('alert_counts', $dashboard);
        $this->assertArrayHasKey('total_alerts', $dashboard);
        $this->assertArrayHasKey('priority_items', $dashboard);
        $this->assertArrayHasKey('reorder_suggestions', $dashboard);
        $this->assertArrayHasKey('estimated_reorder_cost', $dashboard);
        $this->assertArrayHasKey('last_updated', $dashboard);
    }

    public function test_can_detect_low_stock_with_thresholds()
    {
        $detection = $this->inventoryService->detectLowStockWithThresholds();
        
        $this->assertIsArray($detection);
        $this->assertArrayHasKey('alerts', $detection);
        $this->assertArrayHasKey('summary', $detection);
        $this->assertArrayHasKey('generated_at', $detection);
        
        // Check alert structure
        $alerts = $detection['alerts'];
        $this->assertArrayHasKey('critical_stock', $alerts);
        $this->assertArrayHasKey('low_stock', $alerts);
        $this->assertArrayHasKey('out_of_stock', $alerts);
        
        // Check summary structure
        $summary = $detection['summary'];
        $this->assertArrayHasKey('total_alerts', $summary);
        $this->assertArrayHasKey('critical_count', $summary);
        $this->assertArrayHasKey('low_stock_count', $summary);
        $this->assertArrayHasKey('out_of_stock_count', $summary);
    }

    public function test_can_get_comprehensive_audit_trail()
    {
        $auditTrail = $this->inventoryService->getComprehensiveAuditTrail();
        
        $this->assertIsArray($auditTrail);
        $this->assertArrayHasKey('audit_entries', $auditTrail);
        $this->assertArrayHasKey('total_entries', $auditTrail);
        $this->assertArrayHasKey('period', $auditTrail);
        $this->assertArrayHasKey('filters_applied', $auditTrail);
        $this->assertArrayHasKey('generated_at', $auditTrail);
        
        // Check period structure
        $period = $auditTrail['period'];
        $this->assertArrayHasKey('start_date', $period);
        $this->assertArrayHasKey('end_date', $period);
    }
}