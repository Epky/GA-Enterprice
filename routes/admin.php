<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "admin" middleware group.
|
*/

// Admin-only routes
Route::middleware(['auth', 'role.redirect', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    
    // Dashboard section pages
    Route::get('/dashboard/sales-revenue', [\App\Http\Controllers\Admin\DashboardController::class, 'salesRevenue'])->name('dashboard.sales');
    Route::get('/dashboard/customers-channels', [\App\Http\Controllers\Admin\DashboardController::class, 'customersChannels'])->name('dashboard.customers');
    Route::get('/dashboard/inventory-insights', [\App\Http\Controllers\Admin\DashboardController::class, 'inventoryInsights'])->name('dashboard.inventory');
    
    // Analytics routes
    Route::get('/analytics/export', [\App\Http\Controllers\Admin\DashboardController::class, 'exportAnalytics'])->name('analytics.export');
    Route::get('/analytics/data', [\App\Http\Controllers\Admin\DashboardController::class, 'getAnalyticsData'])->name('analytics.data');

    // User management routes
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // Staff management routes (admin only)
    Route::resource('staff', \App\Http\Controllers\Admin\StaffController::class);

    // Category AJAX/API routes for product forms (shared with staff)
    Route::get('categories/active', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'getActive'])
        ->name('categories.active');
    Route::post('categories/inline', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'storeInline'])
        ->name('categories.store-inline');
    Route::delete('categories/{category:id}/inline', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'deleteInline'])
        ->name('categories.delete-inline');
    
    // Brand AJAX/API routes for product forms (shared with staff)
    Route::get('brands/active', [\App\Http\Controllers\Staff\StaffBrandController::class, 'getActive'])
        ->name('brands.active');
    Route::post('brands/inline', [\App\Http\Controllers\Staff\StaffBrandController::class, 'storeInline'])
        ->name('brands.store-inline');
    Route::delete('brands/{brand}/inline', [\App\Http\Controllers\Staff\StaffBrandController::class, 'deleteInline'])
        ->name('brands.delete-inline');

    // Product management routes
    Route::resource('products', \App\Http\Controllers\Admin\AdminProductController::class);
    
    // Product image management routes
    Route::post('products/{product}/images/upload', [\App\Http\Controllers\Admin\AdminProductController::class, 'uploadImages'])
        ->name('products.images.upload');
    Route::delete('products/images/{image}', [\App\Http\Controllers\Admin\AdminProductController::class, 'deleteImage'])
        ->name('products.images.delete');
    Route::post('products/images/{image}/set-primary', [\App\Http\Controllers\Admin\AdminProductController::class, 'setPrimaryImage'])
        ->name('products.images.set-primary');
    
    // Product quick actions
    Route::patch('products/{product}/toggle-featured', [\App\Http\Controllers\Admin\AdminProductController::class, 'toggleFeatured'])
        ->name('products.toggle-featured');
});
