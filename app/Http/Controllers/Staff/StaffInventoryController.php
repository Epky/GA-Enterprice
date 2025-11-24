<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StaffInventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display inventory dashboard with overview and alerts
     */
    public function index(Request $request)
    {
        $location = $request->get('location');
        
        // Get inventory statistics
        $stats = $this->inventoryService->getInventoryStats($location);
        
        // Get low stock alert dashboard data
        $alertDashboard = $this->inventoryService->getLowStockAlertDashboard($location);
        
        // Get recent inventory movements
        $recentMovements = $this->inventoryService->getInventoryMovements([
            'location' => $location
        ], 10);
        
        // Get inventory list with filters
        $inventory = $this->inventoryService->getInventory([
            'location' => $location,
            'search' => $request->get('search'),
            'stock_status' => $request->get('stock_status'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'sort_by' => $request->get('sort_by', 'updated_at'),
            'sort_order' => $request->get('sort_order', 'desc')
        ], 20);

        return view('staff.inventory.index', compact(
            'stats', 
            'alertDashboard', 
            'recentMovements', 
            'inventory'
        ));
    }

    /**
     * Display detailed inventory list with advanced filtering
     */
    public function list(Request $request)
    {
        $inventory = $this->inventoryService->getInventory([
            'location' => $request->get('location'),
            'search' => $request->get('search'),
            'stock_status' => $request->get('stock_status'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'sort_by' => $request->get('sort_by', 'updated_at'),
            'sort_order' => $request->get('sort_order', 'desc')
        ], 50);

        return view('staff.inventory.list', compact('inventory'));
    }

    /**
     * Show stock update form for a specific product
     */
    public function edit(Product $product)
    {
        $product->load(['inventory', 'variants.inventory', 'category', 'brand']);
        
        // Get recent movements for this product
        $recentMovements = $this->inventoryService->getInventoryMovements([
            'product_id' => $product->id
        ], 10);

        return view('staff.inventory.edit', compact('product', 'recentMovements'));
    }

    /**
     * Update stock for a specific product
     * Requirement 3.1: Record changes with timestamp and user information
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer',
            'movement_type' => 'required|in:purchase,sale,return,damage,adjustment,transfer',
            'location' => 'required|string|max:255',
            'variant_id' => 'nullable|exists:product_variants,id',
            'notes' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|max:255'
        ]);

        try {
            $options = [
                'variant_id' => $request->variant_id,
                'location' => $request->location,
                'notes' => $request->notes . ($request->reason ? " (Reason: {$request->reason})" : ''),
                'performed_by' => Auth::id()
            ];

            $inventory = $this->inventoryService->updateStockWithAudit(
                $product,
                $request->quantity,
                $request->movement_type,
                $options
            );

            return redirect()->route('staff.inventory.edit', $product)
                ->with('success', 'Stock updated successfully. New quantity: ' . $inventory->quantity_available);
                
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Stock update failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update stock. Please try again.');
        }
    }

    /**
     * Show bulk stock update form
     */
    public function showBulkUpdate()
    {
        return view('staff.inventory.bulk-update');
    }

    /**
     * Bulk update stock for multiple products
     * Requirement 3.3: Process all changes atomically
     */
    public function bulkUpdate(Request $request)
    {
        // Decode JSON updates if provided as string
        $updates = $request->input('updates');
        if (is_string($updates)) {
            $updates = json_decode($updates, true);
        }

        // Validate the decoded updates
        $validator = validator(['updates' => $updates], [
            'updates' => 'required|array|min:1',
            'updates.*.product_id' => 'required|exists:products,id',
            'updates.*.quantity' => 'required|integer',
            'updates.*.movement_type' => 'required|in:purchase,sale,return,damage,adjustment',
            'updates.*.location' => 'nullable|string|max:255',
            'updates.*.variant_id' => 'nullable|exists:product_variants,id',
            'updates.*.notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Transform updates to include options
            $transformedUpdates = collect($updates)->map(function($update) {
                return [
                    'product_id' => $update['product_id'],
                    'quantity' => $update['quantity'],
                    'movement_type' => $update['movement_type'],
                    'options' => [
                        'variant_id' => $update['variant_id'] ?? null,
                        'location' => $update['location'] ?? 'main_warehouse',
                        'notes' => $update['notes'] ?? null,
                        'performed_by' => Auth::id()
                    ]
                ];
            })->toArray();

            $results = $this->inventoryService->bulkUpdateStock($transformedUpdates);
            
            $successCount = collect($results)->filter(function($result) {
                return !isset($result['error']);
            })->count();
            
            $errorCount = count($results) - $successCount;
            
            if ($errorCount > 0) {
                return redirect()->route('staff.inventory.list')
                    ->with('warning', "Bulk update completed with {$successCount} successes and {$errorCount} errors.");
            }
            
            return redirect()->route('staff.inventory.list')
                ->with('success', "Successfully updated stock for {$successCount} products.");
                
        } catch (\Exception $e) {
            Log::error('Bulk stock update failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Bulk update failed. Please try again.');
        }
    }

    /**
     * Display low stock alerts
     * Requirement 3.2: Display low stock alerts on dashboard
     */
    public function alerts(Request $request)
    {
        $location = $request->get('location');
        $includeProjections = $request->boolean('include_projections', false);
        
        $alerts = $this->inventoryService->detectLowStockWithThresholds([
            'location' => $location,
            'threshold_multiplier' => $request->get('threshold', 1.0),
            'include_projections' => $includeProjections
        ]);

        return view('staff.inventory.alerts', compact('alerts'));
    }

    /**
     * Get low stock alerts data for AJAX requests
     */
    public function getAlertsData(Request $request)
    {
        $location = $request->get('location');
        
        $alertDashboard = $this->inventoryService->getLowStockAlertDashboard($location);
        
        return response()->json($alertDashboard);
    }

    /**
     * Display inventory movements history
     */
    public function movements(Request $request)
    {
        $movements = $this->inventoryService->getInventoryMovements([
            'product_id' => $request->get('product_id'),
            'movement_type' => $request->get('movement_type'),
            'location' => $request->get('location'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'performed_by' => $request->get('performed_by')
        ], 25);

        return view('staff.inventory.movements', compact('movements'));
    }

    /**
     * Display comprehensive inventory reports
     * Requirement 3.3: Add inventory reporting endpoints
     */
    public function reports(Request $request)
    {
        $reportType = $request->get('type', 'overview');
        $location = $request->get('location');
        
        switch ($reportType) {
            case 'stock_tracking':
                $report = $this->inventoryService->getStockTrackingReport([
                    'start_date' => $request->get('start_date'),
                    'end_date' => $request->get('end_date'),
                    'location' => $location
                ]);
                break;
                
            case 'audit_trail':
                $report = $this->inventoryService->getComprehensiveAuditTrail([
                    'start_date' => $request->get('start_date'),
                    'end_date' => $request->get('end_date'),
                    'product_id' => $request->get('product_id'),
                    'user_id' => $request->get('user_id'),
                    'movement_type' => $request->get('movement_type'),
                    'location' => $location
                ]);
                break;
                
            case 'reorder_suggestions':
                $report = [
                    'suggestions' => $this->inventoryService->getReorderSuggestions($location),
                    'stats' => $this->inventoryService->getInventoryStats($location)
                ];
                break;
                
            default:
                $report = [
                    'stats' => $this->inventoryService->getInventoryStats($location),
                    'alerts' => $this->inventoryService->getInventoryAlerts($location),
                    'recent_movements' => $this->inventoryService->getInventoryMovements(['location' => $location], 10)
                ];
        }

        return view('staff.inventory.reports', compact('report', 'reportType'));
    }

    /**
     * Transfer stock between locations
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255|different:from_location',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            
            $this->inventoryService->transferStock(
                $product,
                $request->quantity,
                $request->from_location,
                $request->to_location,
                [
                    'variant_id' => $request->variant_id,
                    'notes' => $request->notes,
                    'performed_by' => Auth::id()
                ]
            );

            return redirect()->back()
                ->with('success', "Successfully transferred {$request->quantity} units from {$request->from_location} to {$request->to_location}");
                
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Stock transfer failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to transfer stock. Please try again.');
        }
    }

    /**
     * Validate inventory levels and show validation results
     */
    public function validate(Request $request)
    {
        $location = $request->get('location');
        
        $validation = $this->inventoryService->validateStockLevels($location);
        
        return view('staff.inventory.validation', compact('validation'));
    }

    /**
     * Export inventory data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $location = $request->get('location');
        
        $inventory = $this->inventoryService->getInventory([
            'location' => $location,
            'stock_status' => $request->get('stock_status'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id')
        ], 1000);

        // For now, return JSON response - can be extended to support CSV/Excel
        return response()->json([
            'data' => $inventory->items(),
            'total' => $inventory->total(),
            'exported_at' => now(),
            'filters' => $request->only(['location', 'stock_status', 'category_id', 'brand_id'])
        ]);
    }

    /**
     * Quick stock adjustment for single product
     */
    public function quickAdjust(Request $request, Product $product)
    {
        $request->validate([
            'adjustment' => 'required|integer',
            'location' => 'required|string|max:255',
            'variant_id' => 'nullable|exists:product_variants,id',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $options = [
                'variant_id' => $request->variant_id,
                'location' => $request->location,
                'notes' => "Quick adjustment: {$request->reason}",
                'performed_by' => Auth::id()
            ];

            $inventory = $this->inventoryService->updateStock(
                $product,
                $request->adjustment,
                'adjustment',
                $options
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'new_quantity' => $inventory->quantity_available
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Quick stock adjustment failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock'
            ], 500);
        }
    }
}