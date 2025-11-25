# Design Document

## Overview

This design implements a real-time low stock alert system that monitors inventory levels per product and provides visual indicators when stock reaches warning (50% of reorder level) or critical (25% of reorder level) thresholds. The system will display alerts on a dedicated page and show alert counts on the dashboard for quick visibility.

The implementation involves:
1. Creating a dedicated alerts view page with severity-based grouping
2. Enhancing the dashboard to display alert counts with color indicators
3. Implementing real-time alert detection using existing InventoryService methods
4. Adding visual indicators (red/yellow/green) for different alert severities
5. Calculating stock percentages relative to reorder levels

## Architecture

The alert system follows a layered architecture integrated with the existing inventory system:

```
┌─────────────────────────────────────┐
│   Staff Dashboard                   │
│   (Alert Count Display)             │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   StaffInventoryController          │
│   (alerts() method)                 │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   InventoryService                  │
│   (detectLowStockWithThresholds)    │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Inventory & Product Models        │
│   (Data Access Layer)               │
└─────────────────────────────────────┘
```

### Key Components

1. **StaffInventoryController**: Handles alert page requests and data retrieval
2. **InventoryService**: Provides alert detection and dashboard data methods
3. **Blade Views**: Display alerts with visual indicators and grouping
4. **Dashboard Integration**: Shows alert counts with click-through navigation

## Components and Interfaces

### Alert Detection Logic

**Existing InventoryService Method (Already Implemented):**
```php
public function detectLowStockWithThresholds(array $options = []): array
{
    // Detects items at different stock levels:
    // - Critical: <= 25% of reorder level
    // - Low: <= 50% of reorder level (configurable)
    // - Out of stock: quantity_available = 0
    
    return [
        'alerts' => [
            'critical_stock' => Collection,
            'low_stock' => Collection,
            'out_of_stock' => Collection,
        ],
        'summary' => [
            'total_alerts' => int,
            'critical_count' => int,
            'low_stock_count' => int,
            'out_of_stock_count' => int,
        ]
    ];
}
```

### Alert View Structure

**View File: resources/views/staff/inventory/alerts.blade.php**
```blade
@extends('layouts.staff')

@section('content')
<div class="container">
    <h1>Inventory Alerts</h1>
    
    <!-- Alert Summary Cards -->
    <div class="alert-summary">
        <div class="alert-card critical">
            <span class="count">{{ $alerts['summary']['critical_count'] }}</span>
            <span class="label">Critical</span>
        </div>
        <div class="alert-card warning">
            <span class="count">{{ $alerts['summary']['low_stock_count'] }}</span>
            <span class="label">Warning</span>
        </div>
        <div class="alert-card error">
            <span class="count">{{ $alerts['summary']['out_of_stock_count'] }}</span>
            <span class="label">Out of Stock</span>
        </div>
    </div>
    
    <!-- Critical Alerts (Red) -->
    @if($alerts['alerts']['critical_stock']->isNotEmpty())
    <section class="alert-section critical">
        <h2>Critical Stock Levels</h2>
        @foreach($alerts['alerts']['critical_stock'] as $alert)
            <!-- Display alert details -->
        @endforeach
    </section>
    @endif
    
    <!-- Out of Stock Alerts (Red) -->
    @if($alerts['alerts']['out_of_stock']->isNotEmpty())
    <section class="alert-section error">
        <h2>Out of Stock</h2>
        @foreach($alerts['alerts']['out_of_stock'] as $alert)
            <!-- Display alert details -->
        @endforeach
    </section>
    @endif
    
    <!-- Warning Alerts (Yellow) -->
    @if($alerts['alerts']['low_stock']->isNotEmpty())
    <section class="alert-section warning">
        <h2>Low Stock Warnings</h2>
        @foreach($alerts['alerts']['low_stock'] as $alert)
            <!-- Display alert details -->
        @endforeach
    </section>
    @endif
</div>
@endsection
```

### Dashboard Integration

**Dashboard Alert Widget:**
```blade
<!-- In resources/views/staff/dashboard.blade.php -->
<div class="alert-widget">
    <h3>Inventory Alerts</h3>
    <div class="alert-counts">
        <a href="{{ route('staff.inventory.alerts') }}" class="alert-badge critical">
            <span class="count">{{ $alertDashboard['alert_counts']['critical'] }}</span>
            <span class="label">Critical</span>
        </a>
        <a href="{{ route('staff.inventory.alerts') }}" class="alert-badge warning">
            <span class="count">{{ $alertDashboard['alert_counts']['warning'] }}</span>
            <span class="label">Warning</span>
        </a>
        <a href="{{ route('staff.inventory.alerts') }}" class="alert-badge error">
            <span class="count">{{ $alertDashboard['alert_counts']['error'] }}</span>
            <span class="label">Out of Stock</span>
        </a>
    </div>
</div>
```

## Data Models

### Alert Severity Levels

```php
// Alert severity classification based on stock percentage
const ALERT_LEVELS = [
    'critical' => [
        'threshold' => 0.25,  // 25% of reorder level
        'color' => 'red',
        'priority' => 1,
    ],
    'warning' => [
        'threshold' => 0.50,  // 50% of reorder level
        'color' => 'yellow',
        'priority' => 2,
    ],
    'healthy' => [
        'threshold' => 1.0,   // Above 50% of reorder level
        'color' => 'green',
        'priority' => 3,
    ],
];
```

### Alert Data Structure

```php
[
    'type' => 'critical_stock|low_stock|out_of_stock',
    'severity' => 'critical|warning|error',
    'inventory' => Inventory,
    'message' => string,
    'action_required' => boolean,
    'suggested_action' => string,
    'stock_percentage' => float,  // Percentage relative to reorder level
    'days_until_stockout' => ?int,
]
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Alert severity classification is consistent
*For any* product with inventory and a reorder level set, when quantity_available is at or below 25% of reorder_level, the system should classify it as critical; when between 26-50%, it should classify as warning; when above 50%, it should not generate an alert (healthy).
**Validates: Requirements 1.1, 1.2, 5.2, 5.3, 5.4**

### Property 2: Alert counts match actual alerts
*For any* location filter, the sum of critical_count + warning_count + out_of_stock_count should equal the total number of alert items returned.
**Validates: Requirements 3.1, 3.2, 3.3**

### Property 3: Products without reorder levels are excluded
*For any* product where reorder_level is null or zero, the system should not generate any alerts for that product.
**Validates: Requirements 1.5**

### Property 4: Alert display order prioritizes severity
*For any* set of alerts, critical alerts should appear before warning alerts in the display order.
**Validates: Requirements 2.2, 4.5**

### Property 5: Stock percentage calculation uses available quantity
*For any* product with alerts, the calculated stock percentage should equal (quantity_available / reorder_level) * 100, using quantity_available and not including reserved stock.
**Validates: Requirements 5.1, 5.5**

### Property 6: Alert data contains required fields
*For any* alert generated, it should contain product name, current stock (quantity_available), reorder level, location, and severity level.
**Validates: Requirements 1.4, 2.3, 4.4**

### Property 7: Location filtering is accurate
*For any* specific location filter, all returned alerts should be from that location only, and no alerts from other locations should be included.
**Validates: Requirements 2.4**

### Property 8: Stock changes trigger alert recalculation
*For any* product, when quantity_available changes, the alert status should be recalculated and reflect the new stock level immediately.
**Validates: Requirements 1.3, 3.5**

### Property 9: Complete alert coverage
*For any* set of products, all products meeting the warning or critical thresholds should appear in the alerts list.
**Validates: Requirements 2.1**

## Error Handling

### Edge Cases

1. **No Reorder Level Set**: Skip alert generation for products without reorder levels
2. **Zero Reorder Level**: Treat as no reorder level set
3. **Negative Stock**: Display as critical alert with special indicator
4. **No Alerts**: Display friendly message indicating all stock levels are healthy
5. **Location Filtering**: Handle null location (show all) vs specific location

### Error Messages

```php
// No alerts message
"All inventory levels are healthy. No alerts at this time."

// No reorder level warning
"This product does not have a reorder level configured."

// Negative stock error
"CRITICAL: Negative stock detected. Immediate investigation required."
```

## Testing Strategy

### Unit Testing

We will write unit tests to verify:
- Alert severity classification logic
- Stock percentage calculations
- Alert count aggregation
- Location filtering
- Products without reorder levels are excluded

**Test Framework**: PHPUnit (Laravel's default)

### Property-Based Testing

We will use property-based testing to verify universal properties across all inputs.

**PBT Library**: **Pest with Faker** for property-based testing in PHP/Laravel.

**Configuration**: Each property test will run a minimum of 100 iterations with randomly generated data.

**Property Test Tagging**: Each property-based test MUST be tagged with a comment explicitly referencing the correctness property using this format:
```php
// Feature: inventory-low-stock-alerts, Property 1: Alert severity classification is consistent
```

**Implementation Approach**:
1. Generate random inventory data with varying stock levels and reorder levels
2. Test that alert classification is consistent across all scenarios
3. Verify alert counts match actual alerts
4. Test that products without reorder levels are excluded
5. Verify stock percentage calculations are accurate

### Test Coverage

- **Unit Tests**: Specific examples and edge cases (no alerts, negative stock, etc.)
- **Property Tests**: Universal properties across random inputs
- **Integration Tests**: End-to-end alert page rendering and dashboard integration

Both unit and property tests are complementary:
- Unit tests catch specific bugs and verify concrete examples
- Property tests verify general correctness across all possible inputs

## Visual Design

### Color Scheme

```css
/* Critical Alerts - Red */
.alert-critical, .alert-badge.critical {
    background-color: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
}

/* Warning Alerts - Yellow/Orange */
.alert-warning, .alert-badge.warning {
    background-color: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
}

/* Error/Out of Stock - Red (darker) */
.alert-error, .alert-badge.error {
    background-color: #fecaca;
    border-color: #dc2626;
    color: #7f1d1d;
}

/* Healthy Stock - Green */
.alert-healthy {
    background-color: #d1fae5;
    border-color: #10b981;
    color: #065f46;
}
```

### Alert Card Layout

```
┌─────────────────────────────────────────────┐
│ [!] Product Name                            │
│                                             │
│ Current Stock: 15 units                     │
│ Reorder Level: 50 units                     │
│ Stock Level: 30% (CRITICAL)                 │
│                                             │
│ Location: Main Warehouse                    │
│ Suggested Action: Immediate reorder required│
│                                             │
│ [View Product] [Reorder Now]                │
└─────────────────────────────────────────────┘
```

## Performance Considerations

1. **Caching**: Cache alert counts for dashboard display (5-minute TTL)
2. **Eager Loading**: Load product and variant relationships to avoid N+1 queries
3. **Pagination**: Paginate alerts if count exceeds 50 items
4. **Index Optimization**: Ensure database indexes on quantity_available and reorder_level columns

## Implementation Notes

- The InventoryService already has `detectLowStockWithThresholds()` method implemented
- The controller already has the `alerts()` method that calls the service
- Main work is creating the Blade view file and styling
- Dashboard integration requires updating the dashboard view to include alert widget
- No database migrations needed - uses existing inventory table structure
