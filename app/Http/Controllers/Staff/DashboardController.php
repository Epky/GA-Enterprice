<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the staff dashboard with statistics and quick access widgets.
     */
    public function index()
    {
        // Get product statistics
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $inactiveProducts = Product::where('status', 'inactive')->count();
        $featuredProducts = Product::where('is_featured', true)->count();
        
        // Get inventory statistics
        $lowStockCount = Inventory::whereColumn('quantity_available', '<=', 'reorder_level')
            ->where('quantity_available', '>', 0)
            ->count();
        
        $outOfStockCount = Inventory::where('quantity_available', '<=', 0)->count();
        
        $totalInventoryValue = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(inventory.quantity_available * products.base_price) as total_value'))
            ->value('total_value') ?? 0;
        
        // Get low stock items for alerts
        $lowStockItems = Inventory::with(['product', 'variant'])
            ->whereColumn('quantity_available', '<=', 'reorder_level')
            ->where('quantity_available', '>', 0)
            ->orderBy('quantity_available', 'asc')
            ->limit(5)
            ->get();
        
        // Get out of stock items
        $outOfStockItems = Inventory::with(['product', 'variant'])
            ->where('quantity_available', '<=', 0)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get recent inventory movements (last 7 days)
        $recentMovements = DB::table('inventory_movements')
            ->join('products', 'inventory_movements.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'inventory_movements.variant_id', '=', 'product_variants.id')
            ->select(
                'inventory_movements.*',
                'products.name as product_name',
                'products.sku as product_sku',
                'product_variants.name as variant_name'
            )
            ->where('inventory_movements.created_at', '>=', now()->subDays(7))
            ->orderBy('inventory_movements.created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get category and brand counts
        $totalCategories = DB::table('categories')->where('is_active', true)->count();
        $totalBrands = DB::table('brands')->where('is_active', true)->count();
        
        return view('staff.dashboard', compact(
            'totalProducts',
            'activeProducts',
            'inactiveProducts',
            'featuredProducts',
            'lowStockCount',
            'outOfStockCount',
            'totalInventoryValue',
            'lowStockItems',
            'outOfStockItems',
            'recentMovements',
            'totalCategories',
            'totalBrands'
        ));
    }
}
