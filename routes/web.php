<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $categories = \App\Models\Category::active()
        ->root()
        ->ordered()
        ->withCount('activeProducts')
        ->get();
    
    return view('welcome', compact('categories'));
})->name('home');

// Public Product Browsing Routes (No authentication required)
Route::get('/products', [App\Http\Controllers\Shop\ShopController::class, 'index'])->name('products.index');
Route::get('/products/category/{category}', [App\Http\Controllers\Shop\ShopController::class, 'category'])->name('products.category');
Route::get('/products/{product}', [App\Http\Controllers\Shop\ShopController::class, 'show'])->name('products.show');

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
