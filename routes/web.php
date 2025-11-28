<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Temporary fix: Use Supabase REST API to bypass connection pooler authentication issue
    // This works because the anon key can access tables without RLS
    try {
        $categories = \App\Models\Category::active()
            ->root()
            ->ordered()
            ->withCount('activeProducts')
            ->get();
    } catch (\Exception $e) {
        // Fallback: If database connection fails, use Supabase API
        Log::warning('Database connection failed, using Supabase API: ' . $e->getMessage());
        $supabase = new \App\Services\SupabaseService();
        $categories = $supabase->getActiveCategories();
    }
    
    return view('welcome', compact('categories'));
})->name('home');

// Public Product Browsing Routes (No authentication required)
Route::get('/products', [App\Http\Controllers\Shop\ShopController::class, 'index'])->name('products.index');
Route::get('/products/category/{category}', [App\Http\Controllers\Shop\ShopController::class, 'category'])->name('products.category');
Route::get('/products/{product}', [App\Http\Controllers\Shop\ShopController::class, 'show'])->name('products.show');

// Cart routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::post('/cart/add/{product}', [App\Http\Controllers\Customer\CartController::class, 'add'])->name('cart.add');
    Route::get('/cart', [App\Http\Controllers\Customer\CartController::class, 'index'])->name('cart.index');
    Route::patch('/cart/item/{cartItem}', [App\Http\Controllers\Customer\CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/item/{cartItem}', [App\Http\Controllers\Customer\CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart/clear', [App\Http\Controllers\Customer\CartController::class, 'clear'])->name('cart.clear');
});

// Generic dashboard route that redirects based on user role
Route::get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = Auth::user();
    
    if ($user && $user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    } elseif ($user && $user->isStaff()) {
        return redirect()->route('staff.dashboard');
    } else {
        return redirect()->route('customer.dashboard');
    }
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Customer routes
Route::middleware(['auth', 'role.redirect', 'customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Customer\CustomerController::class, 'dashboard'])->name('dashboard');
    Route::get('/product/{product}', [App\Http\Controllers\Customer\CustomerController::class, 'show'])->name('product.show');
});
