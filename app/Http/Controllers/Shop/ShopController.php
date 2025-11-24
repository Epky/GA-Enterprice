<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display all products with optional filtering
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'images'])
            ->where('status', 'active');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(12);
        $categories = Category::where('is_active', true)->get();

        return view('shop.index', compact('products', 'categories'));
    }

    /**
     * Display products by category
     */
    public function category(Category $category)
    {
        $products = Product::with(['category', 'brand', 'images'])
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->paginate(12);

        $categories = Category::where('is_active', true)->get();

        return view('shop.category', compact('products', 'category', 'categories'));
    }

    /**
     * Display a single product
     */
    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'images', 'specifications']);

        // Get related products from same category
        $relatedProducts = Product::with(['images'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->limit(4)
            ->get();

        return view('shop.show', compact('product', 'relatedProducts'));
    }
}
