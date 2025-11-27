# Design Document

## Overview

This feature enhances the walk-in transaction interface by displaying real-time stock availability information for cart items and search results. The design focuses on providing immediate visual feedback to staff members about product availability, preventing overselling, and improving the overall transaction experience.

The implementation will leverage existing Product and Inventory models, adding client-side JavaScript for real-time updates and server-side API endpoints for stock calculations. The design maintains the current responsive layout while integrating stock information seamlessly into the existing UI.

## Architecture

### Component Structure

```
┌─────────────────────────────────────────────────────────┐
│                  Walk-In Transaction View                │
│  ┌───────────────────────────────────────────────────┐  │
│  │           Product Search Component                 │  │
│  │  - Search Input                                    │  │
│  │  - Search Results (with stock display)            │  │
│  │  - Quantity Controls                               │  │
│  └───────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────┐  │
│  │              Cart Items Component                  │  │
│  │  - Item List                                       │  │
│  │  - Stock Display (per item)                       │  │
│  │  - Quantity Controls (with stock validation)      │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│              WalkInTransactionController                 │
│  - searchProducts() [enhanced with stock]               │
│  - getAvailableStock() [new endpoint]                   │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│           WalkInTransactionService                       │
│  - calculateAvailableStock()                            │
│  - getCartQuantity()                                     │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│                  Product Model                           │
│  - available_stock (existing attribute)                 │
│  - inventory relationship                                │
└─────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Initial Load**: Server calculates available stock for each cart item and search result
2. **Quantity Change**: Client-side JavaScript updates stock display instantly
3. **Background Sync**: AJAX request updates server-side cart state
4. **Stock Validation**: Server validates stock availability before allowing quantity increases

## Components and Interfaces

### Backend Components

#### WalkInTransactionService Enhancement

```php
class WalkInTransactionService
{
    /**
     * Calculate available stock for a product considering current cart.
     *
     * @param Product $product
     * @param Order $order
     * @return int Available stock quantity
     */
    public function calculateAvailableStock(Product $product, Order $order): int
    {
        // Get total available stock from inventory
        $totalAvailable = $product->available_stock;
        
        // Subtract quantity already in cart
        $cartQuantity = $order->orderItems()
            ->where('product_id', $product->id)
            ->sum('quantity');
        
        return max(0, $totalAvailable - $cartQuantity);
    }
    
    /**
     * Get cart quantity for a specific product.
     *
     * @param Product $product
     * @param Order $order
     * @return int Quantity in cart
     */
    public function getCartQuantity(Product $product, Order $order): int
    {
        return $order->orderItems()
            ->where('product_id', $product->id)
            ->sum('quantity');
    }
}
```

#### Controller Enhancement

```php
class WalkInTransactionController extends Controller
{
    /**
     * Enhanced search with available stock calculation.
     */
    public function searchProducts(Request $request, Order $order)
    {
        $products = $this->transactionService->searchProducts($query);
        
        return response()->json($products->map(function ($product) use ($order) {
            $availableStock = $this->transactionService
                ->calculateAvailableStock($product, $order);
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->effective_price,
                'stock' => $product->total_stock,
                'available_stock' => $availableStock,
                'image' => $imageUrl,
            ];
        }));
    }
}
```

### Frontend Components

#### Cart Item Stock Display

```html
<div class="flex items-center gap-2 mt-1">
    <span class="text-xs font-medium stock-indicator" 
          data-product-id="{{ $item->product_id }}"
          data-available-stock="{{ $availableStock }}">
        <span class="stock-count">{{ $availableStock }}</span> available
    </span>
</div>
```

#### JavaScript Stock Management

```javascript
class StockManager {
    constructor(orderId) {
        this.orderId = orderId;
        this.stockCache = new Map();
    }
    
    /**
     * Update stock display when quantity changes.
     */
    updateStockDisplay(productId, quantityChange) {
        const indicator = document.querySelector(
            `.stock-indicator[data-product-id="${productId}"]`
        );
        
        if (!indicator) return;
        
        const currentStock = parseInt(indicator.dataset.availableStock);
        const newStock = currentStock - quantityChange;
        
        // Update display
        indicator.dataset.availableStock = newStock;
        indicator.querySelector('.stock-count').textContent = newStock;
        
        // Update color coding
        this.updateStockColor(indicator, newStock);
        
        // Update button states
        this.updateQuantityControls(productId, newStock);
    }
    
    /**
     * Apply color coding based on stock level.
     */
    updateStockColor(element, stock) {
        element.classList.remove('text-green-600', 'text-orange-600', 'text-red-600');
        
        if (stock <= 0) {
            element.classList.add('text-red-600');
        } else if (stock <= 10) {
            element.classList.add('text-orange-600');
        } else {
            element.classList.add('text-green-600');
        }
    }
    
    /**
     * Enable/disable quantity controls based on stock.
     */
    updateQuantityControls(productId, availableStock) {
        const plusButton = document.querySelector(
            `[data-product-id="${productId}"] .quantity-plus`
        );
        
        if (plusButton) {
            plusButton.disabled = availableStock <= 0;
            plusButton.classList.toggle('opacity-50', availableStock <= 0);
            plusButton.classList.toggle('cursor-not-allowed', availableStock <= 0);
        }
    }
}
```

## Data Models

### Enhanced Product Response

```typescript
interface ProductSearchResult {
    id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;              // Total stock (available + reserved)
    available_stock: number;    // Available for this transaction
    image: string | null;
}
```

### Cart Item with Stock

```typescript
interface CartItemData {
    id: number;
    product_id: number;
    product_name: string;
    sku: string;
    quantity: number;
    unit_price: number;
    total_price: number;
    available_stock: number;    // Calculated server-side
}
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Stock display format consistency

*For any* cart item with a stock count, the rendered stock display should match the format pattern "{number} available"

**Validates: Requirements 1.2**

### Property 2: Low stock color coding

*For any* stock count where the value is greater than 0 and less than or equal to 10, the stock display should have an orange/warning color class

**Validates: Requirements 1.3**

### Property 3: Adequate stock color coding

*For any* stock count where the value is greater than 10, the stock display should have a green/success color class

**Validates: Requirements 1.4**

### Property 4: Zero stock color coding

*For any* stock count where the value is less than or equal to 0, the stock display should have a red/error color class

**Validates: Requirements 1.5**

### Property 5: Quantity increase decreases available stock

*For any* cart item and any positive quantity increase, the displayed available stock should decrease by exactly that amount

**Validates: Requirements 2.1**

### Property 6: Quantity decrease increases available stock

*For any* cart item and any quantity decrease, the displayed available stock should increase by exactly that amount

**Validates: Requirements 2.2**

### Property 7: Stock calculation with multiple cart items

*For any* product that appears in multiple cart items, the available stock should equal the total product stock minus the sum of all cart item quantities for that product

**Validates: Requirements 2.4**

### Property 8: Color updates after quantity change

*For any* quantity change that results in a new stock level, the color coding should be updated to match the new stock level thresholds

**Validates: Requirements 2.5**

### Property 9: Search results include stock

*For any* product search result, the response should include an available_stock field with a non-null integer value

**Validates: Requirements 3.1**

### Property 10: Search stock calculation considers cart

*For any* product that exists in both search results and the current cart, the displayed available stock in search results should equal total stock minus current cart quantity

**Validates: Requirements 3.2**

### Property 11: Consistent color coding across contexts

*For any* stock level value, the color class applied in search results should match the color class applied in cart items

**Validates: Requirements 3.3**

### Property 12: Quantity increase prevention at stock limit

*For any* cart item where the current quantity equals the available stock, attempting to increase quantity should be prevented

**Validates: Requirements 4.1**

### Property 13: Plus button disabled at stock limit

*For any* cart item where available stock is zero or negative, the plus (+) button should be in a disabled state

**Validates: Requirements 4.2, 4.3**

### Property 14: Button re-enables when stock increases

*For any* cart item where the plus button is disabled due to zero stock, if the available stock increases above zero, the button should become enabled

**Validates: Requirements 4.5**

### Property 15: Consistent stock display across all cart items

*For any* cart with multiple items, all cart items should display stock information using the same format and styling patterns

**Validates: Requirements 5.5**

## Error Handling

### Stock Validation Errors

- **Insufficient Stock**: When attempting to add or increase quantity beyond available stock
  - Response: HTTP 422 with error message "Insufficient stock available"
  - UI: Display error message near the product
  - Action: Prevent the operation and maintain current state

- **Negative Stock Calculation**: When stock calculation results in negative value
  - Response: Treat as zero stock
  - UI: Display "0 available" in red
  - Action: Disable quantity increase controls

### Network Errors

- **Failed Stock Update**: When AJAX request to update quantity fails
  - Response: Revert UI changes
  - UI: Show error notification
  - Action: Reload page to sync state

- **Search Timeout**: When product search takes too long
  - Response: Show timeout message
  - UI: Display "Search timed out, please try again"
  - Action: Allow user to retry search

### Data Consistency Errors

- **Cart-Stock Mismatch**: When server-side stock differs from client-side display
  - Detection: On quantity update response
  - Response: Update client-side to match server
  - UI: Refresh stock displays
  - Action: Log discrepancy for monitoring

## Testing Strategy

### Unit Testing

Unit tests will verify specific behaviors and edge cases:

1. **Stock Calculation Tests**
   - Test `calculateAvailableStock()` with various cart states
   - Test with zero stock, negative stock, and high stock values
   - Test with multiple cart items of same product

2. **Color Coding Tests**
   - Test color class assignment for stock levels: 0, 1, 10, 11, 100
   - Test color transitions when stock changes

3. **Button State Tests**
   - Test plus button disabled when stock is 0
   - Test plus button enabled when stock > 0
   - Test button state transitions

### Property-Based Testing

Property-based tests will verify universal behaviors across all inputs using **Pest PHP** with the **pest-plugin-faker** for data generation. Each test will run a minimum of 100 iterations.

1. **Stock Display Format Property**
   - Generate random stock counts (0-1000)
   - Verify format matches "{number} available" pattern
   - **Validates: Property 1**

2. **Color Coding Properties**
   - Generate random stock counts across all ranges
   - Verify correct color class for each range
   - **Validates: Properties 2, 3, 4**

3. **Quantity-Stock Inverse Relationship**
   - Generate random quantity changes
   - Verify stock changes inversely by same amount
   - **Validates: Properties 5, 6**

4. **Multi-Item Stock Calculation**
   - Generate random carts with duplicate products
   - Verify available stock = total - sum(cart quantities)
   - **Validates: Property 7**

5. **Search-Cart Stock Consistency**
   - Generate random products in cart and search
   - Verify search stock = total - cart quantity
   - **Validates: Property 10**

### Integration Testing

Integration tests will verify end-to-end workflows:

1. **Add Product Flow**
   - Add product from search
   - Verify cart stock display updates
   - Verify search stock display updates

2. **Quantity Change Flow**
   - Change quantity in cart
   - Verify stock display updates
   - Verify color coding updates
   - Verify button states update

3. **Stock Limit Flow**
   - Add product to maximum stock
   - Verify plus button disabled
   - Verify error on attempt to exceed
   - Decrease quantity
   - Verify plus button re-enabled

### Browser Testing

- Test responsive layout on mobile, tablet, desktop
- Test real-time updates without page reload
- Test visual consistency of stock displays
- Test color coding visibility and accessibility

## Performance Considerations

### Client-Side Optimization

- **Debouncing**: Debounce stock calculations during rapid quantity changes
- **Caching**: Cache stock values to minimize recalculation
- **Batch Updates**: Batch DOM updates to minimize reflows

### Server-Side Optimization

- **Eager Loading**: Load inventory relationships with products
- **Query Optimization**: Use aggregate queries for cart quantity sums
- **Response Caching**: Cache product stock for short duration (5-10 seconds)

### Database Optimization

- **Indexes**: Ensure indexes on `inventory.product_id` and `order_items.product_id`
- **Query Efficiency**: Use single query to get all cart quantities
- **Connection Pooling**: Reuse database connections for multiple stock checks

## Security Considerations

### Input Validation

- Validate product IDs exist before stock calculation
- Validate quantity values are positive integers
- Validate order belongs to current staff member

### Authorization

- Verify staff member has permission to view transaction
- Verify staff member can modify the specific order
- Prevent access to other staff members' transactions

### Data Integrity

- Use database transactions for quantity updates
- Implement optimistic locking to prevent race conditions
- Validate stock availability on server before committing changes

## Accessibility

### Screen Reader Support

- Add `aria-label` to stock indicators: "Available stock: {count}"
- Add `aria-live="polite"` to stock displays for dynamic updates
- Provide text alternatives for color-coded information

### Keyboard Navigation

- Ensure quantity controls are keyboard accessible
- Provide keyboard shortcuts for common actions
- Maintain focus management during updates

### Visual Accessibility

- Ensure color contrast meets WCAG AA standards
- Don't rely solely on color for stock status
- Include text indicators alongside color coding
- Support high contrast mode

## Deployment Considerations

### Rollout Strategy

1. **Phase 1**: Deploy backend changes (stock calculation endpoints)
2. **Phase 2**: Deploy frontend changes (stock display in cart)
3. **Phase 3**: Deploy search enhancements (stock in search results)
4. **Phase 4**: Monitor and optimize based on usage

### Monitoring

- Track stock calculation performance
- Monitor error rates for stock validation
- Log stock-cart mismatches for investigation
- Track user interactions with quantity controls

### Rollback Plan

- Feature can be disabled via feature flag
- Frontend changes are non-breaking
- Backend endpoints are additive only
- Database schema unchanged (uses existing fields)
