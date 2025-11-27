<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class InventoryService
{
    /**
     * Movement types that increase inventory.
     */
    private const INBOUND_MOVEMENTS = ['purchase', 'return', 'adjustment'];

    /**
     * Movement types that decrease inventory.
     */
    private const OUTBOUND_MOVEMENTS = ['sale', 'damage', 'adjustment'];

    /**
     * Get inventory records with optional filters.
     */
    public function getInventory(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Inventory::with(['product', 'variant']);

        // Apply location filter
        if (!empty($filters['location'])) {
            $query->atLocation($filters['location']);
        }

        // Apply stock status filter
        if (!empty($filters['stock_status'])) {
            match ($filters['stock_status']) {
                'low_stock' => $query->lowStock(),
                'out_of_stock' => $query->outOfStock(),
                'in_stock' => $query->inStock(),
                default => null,
            };
        }

        // Apply product search
        if (!empty($filters['search'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->search($filters['search']);
            });
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        // Apply brand filter
        if (!empty($filters['brand_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('brand_id', $filters['brand_id']);
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'updated_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get low stock items.
     */
    public function getLowStockItems(?string $location = null): Collection
    {
        $query = Inventory::with(['product', 'variant'])
            ->lowStock();

        if ($location) {
            $query->atLocation($location);
        }

        return $query->get();
    }

    /**
     * Get out of stock items.
     */
    public function getOutOfStockItems(?string $location = null): Collection
    {
        $query = Inventory::with(['product', 'variant'])
            ->outOfStock();

        if ($location) {
            $query->atLocation($location);
        }

        return $query->get();
    }

    /**
     * Update stock quantity for a product/variant.
     */
    public function updateStock(
        Product $product,
        int $quantity,
        string $movementType,
        array $options = []
    ): Inventory {
        return DB::transaction(function () use ($product, $quantity, $movementType, $options) {
            $variantId = $options['variant_id'] ?? null;
            $location = $options['location'] ?? 'main_warehouse';
            $notes = $options['notes'] ?? null;
            $performedBy = $options['performed_by'] ?? Auth::id();

            // Find or create inventory record
            $inventory = $this->findOrCreateInventory($product, $variantId, $location);

            // Validate movement type
            $this->validateMovementType($movementType);

            // Calculate new quantity based on movement type
            $adjustmentQuantity = $this->calculateAdjustmentQuantity($quantity, $movementType);

            // Validate that we don't go negative
            if ($inventory->quantity_available + $adjustmentQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock. Available: ' . $inventory->quantity_available
                ]);
            }

            // Update inventory
            $inventory->adjustQuantity($adjustmentQuantity, $movementType, $notes, $performedBy);

            return $inventory->fresh();
        });
    }

    /**
     * Bulk update stock for multiple products.
     */
    public function bulkUpdateStock(array $updates): array
    {
        $results = [];

        DB::transaction(function () use ($updates, &$results) {
            foreach ($updates as $update) {
                try {
                    $product = Product::findOrFail($update['product_id']);
                    $results[] = $this->updateStock(
                        $product,
                        $update['quantity'],
                        $update['movement_type'] ?? 'adjustment',
                        $update['options'] ?? []
                    );
                } catch (\Exception $e) {
                    $results[] = [
                        'error' => $e->getMessage(),
                        'product_id' => $update['product_id']
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Transfer stock between locations.
     */
    public function transferStock(
        Product $product,
        int $quantity,
        string $fromLocation,
        string $toLocation,
        array $options = []
    ): array {
        return DB::transaction(function () use ($product, $quantity, $fromLocation, $toLocation, $options) {
            $variantId = $options['variant_id'] ?? null;
            $notes = $options['notes'] ?? null;
            $performedBy = $options['performed_by'] ?? Auth::id();

            // Find source inventory
            $sourceInventory = $this->findOrCreateInventory($product, $variantId, $fromLocation);

            // Validate sufficient stock
            if ($sourceInventory->quantity_available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock at source location. Available: ' . $sourceInventory->quantity_available
                ]);
            }

            // Find or create destination inventory
            $destinationInventory = $this->findOrCreateInventory($product, $variantId, $toLocation);

            // Perform transfer
            $sourceInventory->adjustQuantity(-$quantity, 'transfer', $notes, $performedBy);
            $destinationInventory->adjustQuantity($quantity, 'transfer', $notes, $performedBy);

            // Create transfer movement record
            InventoryMovement::create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'movement_type' => 'transfer',
                'quantity' => $quantity,
                'location_from' => $fromLocation,
                'location_to' => $toLocation,
                'notes' => $notes,
                'performed_by' => $performedBy,
            ]);

            return [
                'source' => $sourceInventory->fresh(),
                'destination' => $destinationInventory->fresh(),
            ];
        });
    }

    /**
     * Reserve stock for an order.
     */
    public function reserveStock(Product $product, int $quantity, array $options = []): bool
    {
        return DB::transaction(function () use ($product, $quantity, $options) {
            $variantId = $options['variant_id'] ?? null;
            $location = $options['location'] ?? 'main_warehouse';

            $inventory = $this->findOrCreateInventory($product, $variantId, $location);

            if (!$inventory->reserveQuantity($quantity)) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock to reserve. Available: ' . $inventory->quantity_available
                ]);
            }

            // Create movement record for reservation
            InventoryMovement::create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'movement_type' => 'reservation',
                'quantity' => -$quantity,
                'location_to' => $location,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
                'notes' => $options['notes'] ?? 'Stock reserved',
                'performed_by' => $options['performed_by'] ?? Auth::id(),
            ]);

            return true;
        });
    }

    /**
     * Release reserved stock.
     */
    public function releaseReservedStock(Product $product, int $quantity, array $options = []): bool
    {
        return DB::transaction(function () use ($product, $quantity, $options) {
            $variantId = $options['variant_id'] ?? null;
            $location = $options['location'] ?? 'main_warehouse';

            $inventory = $this->findOrCreateInventory($product, $variantId, $location);

            if (!$inventory->releaseReservedQuantity($quantity)) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient reserved stock to release. Reserved: ' . $inventory->quantity_reserved
                ]);
            }

            // Create movement record for release
            InventoryMovement::create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'movement_type' => 'release',
                'quantity' => $quantity,
                'location_to' => $location,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
                'notes' => $options['notes'] ?? 'Reserved stock released',
                'performed_by' => $options['performed_by'] ?? Auth::id(),
            ]);

            return true;
        });
    }

    /**
     * Get inventory movements with filters.
     */
    public function getInventoryMovements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = InventoryMovement::with(['product', 'variant', 'performedBy']);

        // Apply business-only filter by default (exclude system movements)
        $includeSystemMovements = $filters['include_system_movements'] ?? false;
        if (!$includeSystemMovements) {
            $query->whereIn('movement_type', InventoryMovement::BUSINESS_MOVEMENT_TYPES);
        }

        // Apply product filter
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // Apply movement type filter
        if (!empty($filters['movement_type'])) {
            $query->byType($filters['movement_type']);
        }

        // Apply location filter
        if (!empty($filters['location'])) {
            $query->atLocation($filters['location']);
        }

        // Apply date range filter
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        // Apply user filter
        if (!empty($filters['performed_by'])) {
            $query->where('performed_by', $filters['performed_by']);
        }

        $paginator = $query->recent()->paginate($perPage);

        // Apply grouping if requested
        $groupRelated = $filters['group_related'] ?? true;
        if ($groupRelated) {
            $groupedMovements = $this->groupRelatedMovements($paginator->getCollection());
            $paginator->setCollection($groupedMovements);
        }

        return $paginator;
    }

    /**
     * Group related movements by transaction reference
     * 
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $movements
     * @return \Illuminate\Support\Collection Grouped movements with 'primary' and 'related' keys
     */
    public function groupRelatedMovements($movements): \Illuminate\Support\Collection
    {
        $grouped = collect();
        $processedIds = [];

        // First pass: Process business movements with transaction references
        foreach ($movements as $movement) {
            // Skip if already processed
            if (in_array($movement->id, $processedIds)) {
                continue;
            }

            // Skip system movements in first pass
            if ($movement->isSystemMovement()) {
                continue;
            }

            $transactionRef = $movement->transaction_reference;

            // If no transaction reference, add as ungrouped
            if (!$transactionRef) {
                $grouped->push([
                    'primary' => $movement,
                    'related' => collect(),
                    'transaction_ref' => null,
                ]);
                $processedIds[] = $movement->id;
                continue;
            }

            // This is a business movement with a transaction reference
            // Find related system movements with the same transaction reference
            $relatedMovements = $movements->filter(function ($m) use ($movement, $transactionRef, $processedIds) {
                if ($m->id === $movement->id || in_array($m->id, $processedIds)) {
                    return false;
                }

                $mRef = $m->transaction_reference;
                return $mRef && 
                       $mRef['id'] === $transactionRef['id'] && 
                       $m->product_id === $movement->product_id &&
                       $m->isSystemMovement();
            });

            $grouped->push([
                'primary' => $movement,
                'related' => $relatedMovements->values(),
                'transaction_ref' => $transactionRef['id'],
            ]);

            // Mark all related movements as processed
            $processedIds[] = $movement->id;
            foreach ($relatedMovements as $related) {
                $processedIds[] = $related->id;
            }
        }

        // Second pass: Add any remaining unprocessed movements (system movements without business counterpart)
        foreach ($movements as $movement) {
            if (!in_array($movement->id, $processedIds)) {
                $grouped->push([
                    'primary' => $movement,
                    'related' => collect(),
                    'transaction_ref' => null,
                ]);
                $processedIds[] = $movement->id;
            }
        }

        return $grouped;
    }

    /**
     * Get inventory statistics.
     */
    public function getInventoryStats(?string $location = null): array
    {
        $query = Inventory::query();

        if ($location) {
            $query->atLocation($location);
        }

        $totalItems = $query->count();
        $lowStockItems = (clone $query)->lowStock()->count();
        $outOfStockItems = (clone $query)->outOfStock()->count();
        $totalValue = $this->calculateInventoryValue($location);

        return [
            'total_items' => $totalItems,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'in_stock_items' => $totalItems - $outOfStockItems,
            'total_value' => $totalValue,
            'low_stock_percentage' => $totalItems > 0 ? round(($lowStockItems / $totalItems) * 100, 2) : 0,
        ];
    }

    /**
     * Get reorder suggestions.
     */
    public function getReorderSuggestions(?string $location = null): Collection
    {
        $query = Inventory::with(['product', 'variant'])
            ->lowStock();

        if ($location) {
            $query->atLocation($location);
        }

        $inventories = $query->get();
        
        // Transform the data but keep it as an Eloquent Collection
        $inventories->transform(function ($inventory) {
            $inventory->suggested_quantity = $inventory->suggested_restock_quantity;
            $inventory->estimated_cost = $inventory->suggested_restock_quantity * ($inventory->product->cost_price ?? 0);
            return $inventory;
        });

        return $inventories;
    }

    /**
     * Find or create inventory record.
     */
    private function findOrCreateInventory(Product $product, ?int $variantId, string $location): Inventory
    {
        return Inventory::firstOrCreate([
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'location' => $location,
        ], [
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'quantity_sold' => 0,
            'reorder_level' => 10,
            'reorder_quantity' => 50,
        ]);
    }

    /**
     * Validate movement type.
     */
    private function validateMovementType(string $movementType): void
    {
        $validTypes = array_merge(self::INBOUND_MOVEMENTS, self::OUTBOUND_MOVEMENTS, ['transfer']);

        if (!in_array($movementType, $validTypes)) {
            throw ValidationException::withMessages([
                'movement_type' => 'Invalid movement type: ' . $movementType
            ]);
        }
    }

    /**
     * Calculate adjustment quantity based on movement type.
     */
    private function calculateAdjustmentQuantity(int $quantity, string $movementType): int
    {
        if (in_array($movementType, self::OUTBOUND_MOVEMENTS) && $movementType !== 'adjustment') {
            return -abs($quantity);
        }

        return $quantity;
    }

    /**
     * Calculate total inventory value.
     */
    private function calculateInventoryValue(?string $location = null): float
    {
        $query = Inventory::with('product');

        if ($location) {
            $query->atLocation($location);
        }

        return $query->get()->sum(function ($inventory) {
            $costPrice = $inventory->product->cost_price ?? $inventory->product->base_price ?? 0;
            return $inventory->quantity_available * $costPrice;
        });
    }

    /**
     * Get inventory alerts with enhanced functionality.
     */
    public function getInventoryAlerts(?string $location = null): array
    {
        $lowStockItems = $this->getLowStockItems($location);
        $outOfStockItems = $this->getOutOfStockItems($location);
        $criticalStockItems = $this->getCriticalStockItems($location);

        return [
            'critical_stock' => $criticalStockItems->map(function ($inventory) {
                return [
                    'type' => 'critical_stock',
                    'message' => "{$inventory->display_name} is critically low (Available: {$inventory->quantity_available}, Reorder Level: {$inventory->reorder_level})",
                    'inventory' => $inventory,
                    'severity' => 'critical',
                    'action_required' => true,
                    'suggested_action' => 'Immediate reorder required',
                ];
            }),
            'low_stock' => $lowStockItems->map(function ($inventory) {
                return [
                    'type' => 'low_stock',
                    'message' => "{$inventory->display_name} is running low (Available: {$inventory->quantity_available}, Reorder Level: {$inventory->reorder_level})",
                    'inventory' => $inventory,
                    'severity' => 'warning',
                    'action_required' => false,
                    'suggested_action' => 'Consider reordering soon',
                ];
            }),
            'out_of_stock' => $outOfStockItems->map(function ($inventory) {
                return [
                    'type' => 'out_of_stock',
                    'message' => "{$inventory->display_name} is out of stock",
                    'inventory' => $inventory,
                    'severity' => 'error',
                    'action_required' => true,
                    'suggested_action' => 'Restock immediately',
                ];
            }),
        ];
    }

    /**
     * Get critical stock items (below 50% of reorder level).
     */
    public function getCriticalStockItems(?string $location = null): Collection
    {
        $query = Inventory::with(['product', 'variant'])
            ->whereRaw('quantity_available <= (reorder_level * 0.5)');

        if ($location) {
            $query->atLocation($location);
        }

        return $query->get();
    }

    /**
     * Get comprehensive stock tracking report.
     */
    public function getStockTrackingReport(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30);
        $endDate = $filters['end_date'] ?? Carbon::now();
        $location = $filters['location'] ?? null;

        // Get movement summary
        $movementQuery = InventoryMovement::query()
            ->dateRange($startDate, $endDate);

        if ($location) {
            $movementQuery->atLocation($location);
        }

        $movements = $movementQuery->get();

        // Calculate movement statistics
        $movementStats = [
            'total_movements' => $movements->count(),
            'inbound_movements' => $movements->where('quantity', '>', 0)->count(),
            'outbound_movements' => $movements->where('quantity', '<', 0)->count(),
            'total_quantity_in' => $movements->where('quantity', '>', 0)->sum('quantity'),
            'total_quantity_out' => abs($movements->where('quantity', '<', 0)->sum('quantity')),
        ];

        // Get current inventory status
        $inventoryStats = $this->getInventoryStats($location);

        // Get alerts
        $alerts = $this->getInventoryAlerts($location);

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'location' => $location,
            ],
            'movement_statistics' => $movementStats,
            'inventory_statistics' => $inventoryStats,
            'alerts' => $alerts,
            'recent_movements' => $movements->sortByDesc('created_at')->take(10)->values(),
        ];
    }

    /**
     * Create comprehensive audit trail entry.
     */
    public function createAuditTrail(
        Product $product,
        string $action,
        array $changes,
        ?int $variantId = null,
        ?string $location = null,
        ?string $notes = null
    ): void {
        $auditData = [
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'action' => $action,
            'changes' => $changes,
            'location' => $location,
            'notes' => $notes,
            'performed_by' => Auth::id(),
            'performed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Log the audit trail
        Log::channel('inventory')->info('Inventory audit trail', $auditData);

        // Store in cache for recent activity
        $cacheKey = "inventory_audit_recent";
        $recentAudits = Cache::get($cacheKey, []);
        array_unshift($recentAudits, $auditData);
        $recentAudits = array_slice($recentAudits, 0, 100); // Keep last 100 entries
        Cache::put($cacheKey, $recentAudits, now()->addHours(24));
    }

    /**
     * Get recent audit trail entries.
     */
    public function getRecentAuditTrail(int $limit = 50): array
    {
        $cacheKey = "inventory_audit_recent";
        $recentAudits = Cache::get($cacheKey, []);
        
        return array_slice($recentAudits, 0, $limit);
    }

    /**
     * Enhanced stock update with comprehensive audit trail.
     */
    public function updateStockWithAudit(
        Product $product,
        int $quantity,
        string $movementType,
        array $options = []
    ): Inventory {
        $variantId = $options['variant_id'] ?? null;
        $location = $options['location'] ?? 'main_warehouse';
        $notes = $options['notes'] ?? null;
        $performedBy = $options['performed_by'] ?? Auth::id();

        // Get current inventory for audit trail
        $inventory = $this->findOrCreateInventory($product, $variantId, $location);
        $oldQuantity = $inventory->quantity_available;

        // Perform the stock update
        $updatedInventory = $this->updateStock($product, $quantity, $movementType, $options);

        // Create comprehensive audit trail
        $changes = [
            'old_quantity' => $oldQuantity,
            'new_quantity' => $updatedInventory->quantity_available,
            'quantity_change' => $quantity,
            'movement_type' => $movementType,
        ];

        $this->createAuditTrail(
            $product,
            'stock_update',
            $changes,
            $variantId,
            $location,
            $notes
        );

        return $updatedInventory;
    }

    /**
     * Advanced stock tracking with detailed movement history.
     * Requirement 3.1: Record changes with timestamp and user information
     */
    public function trackStockMovement(
        Product $product,
        int $quantity,
        string $movementType,
        array $options = []
    ): InventoryMovement {
        $variantId = $options['variant_id'] ?? null;
        $location = $options['location'] ?? 'main_warehouse';
        $notes = $options['notes'] ?? null;
        $performedBy = $options['performed_by'] ?? Auth::id();
        $referenceType = $options['reference_type'] ?? null;
        $referenceId = $options['reference_id'] ?? null;

        // Create detailed movement record with full audit trail
        $movement = InventoryMovement::create([
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'location_from' => $options['location_from'] ?? null,
            'location_to' => $location,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'performed_by' => $performedBy,
            'created_at' => now(),
        ]);

        // Log detailed audit information
        Log::channel('inventory')->info('Stock movement tracked', [
            'movement_id' => $movement->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variant_id' => $variantId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'location' => $location,
            'performed_by' => $performedBy,
            'user_name' => Auth::user()?->name ?? 'System',
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $movement;
    }

    /**
     * Enhanced low stock detection with configurable thresholds.
     * Requirement 3.2: Display low stock alerts on dashboard
     */
    public function detectLowStockWithThresholds(array $options = []): array
    {
        $location = $options['location'] ?? null;
        $customThreshold = $options['threshold_multiplier'] ?? 0.5;
        $includeProjections = $options['include_projections'] ?? false;

        $query = Inventory::with(['product', 'variant'])
            ->whereNotNull('reorder_level')
            ->where('reorder_level', '>', 0);

        if ($location) {
            $query->atLocation($location);
        }

        // Get items at different stock levels (non-overlapping categories)
        // Out of stock: quantity_available = 0
        $outOfStockItems = (clone $query)->where('quantity_available', '=', 0)->get();
        
        // Critical: 0 < quantity_available <= 25% of reorder_level
        $criticalItems = (clone $query)
            ->where('quantity_available', '>', 0)
            ->whereRaw('quantity_available <= (reorder_level * 0.25)')
            ->get();
        
        // Low stock: 25% < quantity_available <= threshold (default 50%) of reorder_level
        $lowItems = (clone $query)
            ->whereRaw('quantity_available > (reorder_level * 0.25)')
            ->whereRaw('quantity_available <= (reorder_level * ?)', [$customThreshold])
            ->get();

        $alerts = [
            'critical_stock' => $criticalItems->map(function ($inventory) use ($includeProjections) {
                $alert = [
                    'type' => 'critical_stock',
                    'severity' => 'critical',
                    'inventory' => $inventory,
                    'message' => "CRITICAL: {$inventory->display_name} has only {$inventory->quantity_available} units left (Reorder at: {$inventory->reorder_level})",
                    'action_required' => true,
                    'suggested_action' => 'Immediate reorder required',
                    'days_until_stockout' => $this->calculateDaysUntilStockout($inventory),
                ];

                if ($includeProjections) {
                    $alert['projected_stockout_date'] = $this->projectStockoutDate($inventory);
                    $alert['recommended_order_quantity'] = $this->calculateRecommendedOrderQuantity($inventory);
                }

                return $alert;
            }),
            'low_stock' => $lowItems->map(function ($inventory) use ($includeProjections) {
                $alert = [
                    'type' => 'low_stock',
                    'severity' => 'warning',
                    'inventory' => $inventory,
                    'message' => "LOW STOCK: {$inventory->display_name} is running low ({$inventory->quantity_available} units, Reorder at: {$inventory->reorder_level})",
                    'action_required' => false,
                    'suggested_action' => 'Consider reordering soon',
                    'days_until_reorder' => max(0, $inventory->reorder_level - $inventory->quantity_available),
                ];

                if ($includeProjections) {
                    $alert['projected_reorder_date'] = $this->projectReorderDate($inventory);
                    $alert['recommended_order_quantity'] = $this->calculateRecommendedOrderQuantity($inventory);
                }

                return $alert;
            }),
            'out_of_stock' => $outOfStockItems->map(function ($inventory) {
                return [
                    'type' => 'out_of_stock',
                    'severity' => 'error',
                    'inventory' => $inventory,
                    'message' => "OUT OF STOCK: {$inventory->display_name} is completely out of stock",
                    'action_required' => true,
                    'suggested_action' => 'Restock immediately',
                    'days_out_of_stock' => $this->calculateDaysOutOfStock($inventory),
                ];
            }),
        ];

        // Generate summary statistics
        $summary = [
            'total_alerts' => $criticalItems->count() + $lowItems->count() + $outOfStockItems->count(),
            'critical_count' => $criticalItems->count(),
            'low_stock_count' => $lowItems->count(),
            'out_of_stock_count' => $outOfStockItems->count(),
            'estimated_revenue_at_risk' => $this->calculateRevenueAtRisk($outOfStockItems),
            'total_reorder_cost' => $this->calculateTotalReorderCost($criticalItems->merge($lowItems)),
        ];

        return [
            'alerts' => $alerts,
            'summary' => $summary,
            'generated_at' => now(),
            'location' => $location,
            'threshold_multiplier' => $customThreshold,
        ];
    }

    /**
     * Comprehensive audit trail system for all inventory changes.
     * Requirement 3.4: Maintain complete audit trail of all stock changes
     */
    public function getComprehensiveAuditTrail(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30);
        $endDate = $filters['end_date'] ?? Carbon::now();
        $productId = $filters['product_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $movementType = $filters['movement_type'] ?? null;
        $location = $filters['location'] ?? null;

        // Get movement records with full details
        $movementsQuery = InventoryMovement::with(['product', 'variant', 'performedBy'])
            ->dateRange($startDate, $endDate);

        if ($productId) {
            $movementsQuery->where('product_id', $productId);
        }

        if ($userId) {
            $movementsQuery->where('performed_by', $userId);
        }

        if ($movementType) {
            $movementsQuery->byType($movementType);
        }

        if ($location) {
            $movementsQuery->atLocation($location);
        }

        $movements = $movementsQuery->recent()->get();

        // Get cached audit trail entries
        $cachedAudits = $this->getRecentAuditTrail(100);

        // Combine and format audit data
        $auditEntries = $movements->map(function ($movement) {
            return [
                'id' => $movement->id,
                'type' => 'movement',
                'timestamp' => $movement->created_at,
                'product_name' => $movement->display_name,
                'action' => $movement->movement_type_label,
                'quantity_change' => $movement->quantity,
                'location' => $movement->location_display,
                'performed_by' => $movement->performedBy?->name ?? 'System',
                'notes' => $movement->notes,
                'reference' => $movement->reference_display,
                'details' => [
                    'movement_type' => $movement->movement_type,
                    'direction' => $movement->movement_direction,
                    'absolute_quantity' => $movement->absolute_quantity,
                    'product_id' => $movement->product_id,
                    'variant_id' => $movement->variant_id,
                ],
            ];
        });

        // Add cached audit entries
        foreach ($cachedAudits as $audit) {
            $auditEntries->push([
                'type' => 'audit',
                'timestamp' => $audit['performed_at'],
                'product_name' => Product::find($audit['product_id'])?->name ?? 'Unknown Product',
                'action' => $audit['action'],
                'changes' => $audit['changes'],
                'location' => $audit['location'],
                'performed_by' => User::find($audit['performed_by'])?->name ?? 'Unknown User',
                'notes' => $audit['notes'],
                'details' => [
                    'ip_address' => $audit['ip_address'],
                    'user_agent' => $audit['user_agent'],
                ],
            ]);
        }

        // Sort by timestamp descending
        $auditEntries = $auditEntries->sortByDesc('timestamp')->values();

        return [
            'audit_entries' => $auditEntries,
            'total_entries' => $auditEntries->count(),
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'filters_applied' => array_filter([
                'product_id' => $productId,
                'user_id' => $userId,
                'movement_type' => $movementType,
                'location' => $location,
            ]),
            'generated_at' => now(),
        ];
    }

    /**
     * Calculate days until stockout based on current usage patterns.
     */
    private function calculateDaysUntilStockout(Inventory $inventory): ?int
    {
        // Get recent movement data to calculate average daily usage
        $recentMovements = InventoryMovement::where('product_id', $inventory->product_id)
            ->where('variant_id', $inventory->variant_id)
            ->where('movement_type', 'sale')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();

        if ($recentMovements->isEmpty()) {
            return null;
        }

        $totalSold = $recentMovements->sum(function ($movement) {
            return abs($movement->quantity);
        });

        $averageDailyUsage = $totalSold / 30;

        if ($averageDailyUsage <= 0) {
            return null;
        }

        return (int) ceil($inventory->quantity_available / $averageDailyUsage);
    }

    /**
     * Project stockout date based on usage patterns.
     */
    private function projectStockoutDate(Inventory $inventory): ?Carbon
    {
        $daysUntilStockout = $this->calculateDaysUntilStockout($inventory);
        
        return $daysUntilStockout ? Carbon::now()->addDays($daysUntilStockout) : null;
    }

    /**
     * Project reorder date based on current stock and usage.
     */
    private function projectReorderDate(Inventory $inventory): ?Carbon
    {
        $daysUntilReorder = $this->calculateDaysUntilStockout($inventory);
        
        if (!$daysUntilReorder) {
            return null;
        }

        $daysUntilReorderLevel = max(0, $inventory->quantity_available - $inventory->reorder_level);
        
        return Carbon::now()->addDays($daysUntilReorderLevel);
    }

    /**
     * Calculate recommended order quantity based on usage patterns.
     */
    private function calculateRecommendedOrderQuantity(Inventory $inventory): int
    {
        // Base recommendation on reorder quantity, but adjust based on recent usage
        $baseQuantity = $inventory->reorder_quantity;
        
        // Get usage trend over last 30 days
        $recentUsage = InventoryMovement::where('product_id', $inventory->product_id)
            ->where('variant_id', $inventory->variant_id)
            ->where('movement_type', 'sale')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum('quantity');

        $monthlyUsage = abs($recentUsage);
        
        // Recommend at least 2 months of stock, but not less than reorder quantity
        $recommendedQuantity = max($baseQuantity, $monthlyUsage * 2);
        
        return (int) $recommendedQuantity;
    }

    /**
     * Calculate days the item has been out of stock.
     */
    private function calculateDaysOutOfStock(Inventory $inventory): int
    {
        $lastInStockMovement = InventoryMovement::where('product_id', $inventory->product_id)
            ->where('variant_id', $inventory->variant_id)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastInStockMovement) {
            return 0;
        }

        return Carbon::now()->diffInDays($lastInStockMovement->created_at);
    }

    /**
     * Calculate potential revenue at risk from out of stock items.
     */
    private function calculateRevenueAtRisk(Collection $outOfStockItems): float
    {
        return $outOfStockItems->sum(function ($inventory) {
            // Estimate daily sales based on recent history
            $recentSales = InventoryMovement::where('product_id', $inventory->product_id)
                ->where('variant_id', $inventory->variant_id)
                ->where('movement_type', 'sale')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->sum('quantity');

            $averageDailySales = abs($recentSales) / 7;
            $daysOutOfStock = $this->calculateDaysOutOfStock($inventory);
            $productPrice = $inventory->product->base_price ?? 0;

            return $averageDailySales * $daysOutOfStock * $productPrice;
        });
    }

    /**
     * Calculate total cost for recommended reorders.
     */
    private function calculateTotalReorderCost(Collection $items): float
    {
        return $items->sum(function ($inventory) {
            $recommendedQuantity = $this->calculateRecommendedOrderQuantity($inventory);
            $costPrice = $inventory->product->cost_price ?? $inventory->product->base_price ?? 0;
            
            return $recommendedQuantity * $costPrice;
        });
    }

    /**
     * Get low stock alert dashboard data.
     */
    public function getLowStockAlertDashboard(?string $location = null): array
    {
        $alerts = $this->getInventoryAlerts($location);
        
        // Count alerts by severity
        $alertCounts = [
            'critical' => $alerts['critical_stock']->count(),
            'error' => $alerts['out_of_stock']->count(),
            'warning' => $alerts['low_stock']->count(),
        ];

        // Get top priority items (critical + out of stock)
        $priorityItems = $alerts['critical_stock']->merge($alerts['out_of_stock'])
            ->sortBy(function ($alert) {
                return $alert['inventory']->quantity_available;
            })
            ->take(10)
            ->values(); // Re-index the collection

        // Calculate estimated reorder cost
        $reorderSuggestions = $this->getReorderSuggestions($location);
        $totalReorderCost = $reorderSuggestions->sum('estimated_cost');

        return [
            'alert_counts' => $alertCounts,
            'total_alerts' => array_sum($alertCounts),
            'priority_items' => $priorityItems,
            'reorder_suggestions' => $reorderSuggestions->take(5),
            'estimated_reorder_cost' => $totalReorderCost,
            'last_updated' => now(),
        ];
    }

    /**
     * Validate stock levels and return validation results.
     */
    public function validateStockLevels(?string $location = null): array
    {
        $query = Inventory::with(['product', 'variant']);
        
        if ($location) {
            $query->atLocation($location);
        }

        $inventories = $query->get();
        $issues = [];

        foreach ($inventories as $inventory) {
            // Check for negative stock
            if ($inventory->quantity_available < 0) {
                $issues[] = [
                    'type' => 'negative_stock',
                    'severity' => 'critical',
                    'inventory' => $inventory,
                    'message' => "Negative stock detected: {$inventory->display_name}",
                ];
            }

            // Check for unrealistic reorder levels
            if ($inventory->reorder_level > $inventory->reorder_quantity) {
                $issues[] = [
                    'type' => 'invalid_reorder_config',
                    'severity' => 'warning',
                    'inventory' => $inventory,
                    'message' => "Reorder level exceeds reorder quantity: {$inventory->display_name}",
                ];
            }

            // Check for excessive reserved stock
            if ($inventory->quantity_reserved > $inventory->quantity_available + $inventory->quantity_reserved) {
                $issues[] = [
                    'type' => 'excessive_reserved',
                    'severity' => 'error',
                    'inventory' => $inventory,
                    'message' => "Reserved stock exceeds total stock: {$inventory->display_name}",
                ];
            }
        }

        return [
            'total_items_checked' => $inventories->count(),
            'issues_found' => count($issues),
            'issues' => $issues,
            'validation_passed' => empty($issues),
            'checked_at' => now(),
        ];
    }
}