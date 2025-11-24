<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function dashboard(Request $request)
    {
        $query = Product::with(['category', 'brand', 'primaryImage', 'inventory'])
            ->where('status', 'active')
            ->where('is_featured', true);

        // Get all active products with filters
        $productsQuery = Product::with(['category', 'brand', 'primaryImage', 'inventory'])
            ->where('status', 'active');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $productsQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $productsQuery->where('category_id', $request->category);
        }

        // Brand filter
        if ($request->filled('brand')) {
            $productsQuery->where('brand_id', $request->brand);
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $productsQuery->where('base_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $productsQuery->where('base_price', '<=', $request->max_price);
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_low':
                $productsQuery->orderBy('base_price', 'asc');
                break;
            case 'price_high':
                $productsQuery->orderBy('base_price', 'desc');
                break;
            case 'name':
                $productsQuery->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $productsQuery->orderBy('created_at', 'desc');
                break;
        }

        $products = $productsQuery->paginate(12);
        $featuredProducts = $query->take(4)->get();
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();

        return view('customer.dashboard', compact('products', 'featuredProducts', 'categories', 'brands'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'images', 'variants', 'specifications', 'inventory']);
        
        // Get related products from same category
        $relatedProducts = Product::with(['primaryImage'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->take(4)
            ->get();

        return view('customer.product-detail', compact('product', 'relatedProducts'));
    }
}
