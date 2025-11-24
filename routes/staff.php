<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
|
| Here is where you can register staff routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "staff" middleware group.
|
*/

// Staff routes with authentication and role-based middleware
Route::middleware(['auth', 'role.redirect', 'staff'])->prefix('staff')->name('staff.')->group(function () {
    // Staff dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Staff\DashboardController::class, 'index'])
        ->name('dashboard');
    
    // Product management routes with route model binding
    Route::resource('products', \App\Http\Controllers\Staff\StaffProductController::class);
    
    // Product AJAX/API routes for quick operations
    Route::patch('products/{product}/toggle-featured', [\App\Http\Controllers\Staff\StaffProductController::class, 'toggleFeatured'])
        ->name('products.toggle-featured');
    Route::post('products/{product}/duplicate', [\App\Http\Controllers\Staff\StaffProductController::class, 'duplicate'])
        ->name('products.duplicate');
    Route::post('products/bulk-update-status', [\App\Http\Controllers\Staff\StaffProductController::class, 'bulkUpdateStatus'])
        ->name('products.bulk-update-status');
    Route::get('products-data', [\App\Http\Controllers\Staff\StaffProductController::class, 'getData'])
        ->name('products.data');
    Route::patch('products/{product}/quick-update', [\App\Http\Controllers\Staff\StaffProductController::class, 'quickUpdate'])
        ->name('products.quick-update');
    
    // Product visibility and status management routes
    Route::get('products/visibility/manage', [\App\Http\Controllers\Staff\StaffProductController::class, 'manageVisibility'])
        ->name('products.visibility.manage');
    Route::patch('products/{product}/visibility', [\App\Http\Controllers\Staff\StaffProductController::class, 'updateVisibility'])
        ->name('products.visibility.update');
    Route::post('products/visibility/bulk-update', [\App\Http\Controllers\Staff\StaffProductController::class, 'bulkUpdateVisibility'])
        ->name('products.visibility.bulk-update');
    Route::patch('products/{product}/toggle-flag', [\App\Http\Controllers\Staff\StaffProductController::class, 'toggleMarketingFlag'])
        ->name('products.toggle-flag');
    Route::get('products/{product}/preview', [\App\Http\Controllers\Staff\StaffProductController::class, 'customerPreview'])
        ->name('products.preview');
    Route::patch('products/{product}/quick-toggle-visibility', [\App\Http\Controllers\Staff\StaffProductController::class, 'quickToggleVisibility'])
        ->name('products.quick-toggle-visibility');
    
    // Image management API routes with route model binding
    Route::post('products/images/{image}/set-primary', [\App\Http\Controllers\Staff\StaffProductController::class, 'setPrimaryImage'])
        ->name('products.images.set-primary');
    Route::delete('products/images/{image}', [\App\Http\Controllers\Staff\StaffProductController::class, 'deleteImage'])
        ->name('products.images.delete');
    Route::post('products/{product}/images/reorder', [\App\Http\Controllers\Staff\StaffProductController::class, 'reorderImages'])
        ->name('products.images.reorder');
    Route::post('products/{product}/images/upload', [\App\Http\Controllers\Staff\StaffProductController::class, 'uploadImages'])
        ->name('products.images.upload');
    
    // Inventory management routes with route model binding
    Route::get('inventory', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'index'])
        ->name('inventory.index');
    Route::get('inventory/list', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'list'])
        ->name('inventory.list');
    Route::get('inventory/{product}/edit', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'edit'])
        ->name('inventory.edit');
    Route::patch('inventory/{product}', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'update'])
        ->name('inventory.update');
    Route::get('inventory/bulk-update', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'showBulkUpdate'])
        ->name('inventory.bulk-update.form');
    Route::post('inventory/bulk-update', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'bulkUpdate'])
        ->name('inventory.bulk-update');
    
    // Inventory AJAX/API routes
    Route::get('inventory/alerts', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'alerts'])
        ->name('inventory.alerts');
    Route::get('inventory/alerts-data', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'getAlertsData'])
        ->name('inventory.alerts-data');
    Route::get('inventory/movements', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'movements'])
        ->name('inventory.movements');
    Route::get('inventory/reports', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'reports'])
        ->name('inventory.reports');
    Route::post('inventory/transfer', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'transfer'])
        ->name('inventory.transfer');
    Route::get('inventory/validate', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'validate'])
        ->name('inventory.validate');
    Route::get('inventory/export', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'export'])
        ->name('inventory.export');
    Route::patch('inventory/{product}/quick-adjust', [\App\Http\Controllers\Staff\StaffInventoryController::class, 'quickAdjust'])
        ->name('inventory.quick-adjust');
    
    // Category AJAX/API routes - MUST come BEFORE resource routes to avoid conflicts
    // Debug route - TEMPORARY for troubleshooting
    Route::post('categories/inline-debug', function(\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Log::info('=== Inline Category Debug ===', [
            'timestamp' => now()->toDateTimeString(),
            'all_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'csrf_token_header' => $request->header('X-CSRF-TOKEN'),
            'csrf_token_input' => $request->input('_token'),
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? null,
            'user_role' => auth()->user()->role ?? null,
            'session_id' => session()->getId(),
            'ip' => $request->ip()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Debug info logged to storage/logs/laravel.log',
            'received_data' => $request->all(),
            'user' => auth()->user() ? [
                'id' => auth()->id(),
                'email' => auth()->user()->email,
                'role' => auth()->user()->role
            ] : null
        ]);
    })->name('categories.inline-debug');
    
    Route::post('categories/inline', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'storeInline'])
        ->name('categories.store-inline');
    Route::get('categories/active', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'getActive'])
        ->name('categories.active');
    Route::get('categories/tree', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'getTree'])
        ->name('categories.tree');
    Route::post('categories/reorder', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'reorder'])
        ->name('categories.reorder');
    Route::post('categories/bulk-action', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'bulkAction'])
        ->name('categories.bulk-action');
    Route::patch('categories/{category}/move', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'move'])
        ->name('categories.move');
    Route::patch('categories/{category}/toggle-status', [\App\Http\Controllers\Staff\StaffCategoryController::class, 'toggleStatus'])
        ->name('categories.toggle-status');
    
    // Category management routes with route model binding
    Route::resource('categories', \App\Http\Controllers\Staff\StaffCategoryController::class);
    
    // Brand AJAX/API routes - MUST come BEFORE resource routes to avoid conflicts
    Route::post('brands/inline', [\App\Http\Controllers\Staff\StaffBrandController::class, 'storeInline'])
        ->name('brands.store-inline');
    Route::get('brands/active', [\App\Http\Controllers\Staff\StaffBrandController::class, 'getActive'])
        ->name('brands.active');
    Route::get('brands-data', [\App\Http\Controllers\Staff\StaffBrandController::class, 'getData'])
        ->name('brands.data');
    Route::get('brands/stats', [\App\Http\Controllers\Staff\StaffBrandController::class, 'getStats'])
        ->name('brands.stats');
    Route::get('brands/export', [\App\Http\Controllers\Staff\StaffBrandController::class, 'export'])
        ->name('brands.export');
    Route::get('brands/search', [\App\Http\Controllers\Staff\StaffBrandController::class, 'search'])
        ->name('brands.search');
    Route::post('brands/bulk-action', [\App\Http\Controllers\Staff\StaffBrandController::class, 'bulkAction'])
        ->name('brands.bulk-action');
    Route::patch('brands/{brand}/toggle-status', [\App\Http\Controllers\Staff\StaffBrandController::class, 'toggleStatus'])
        ->name('brands.toggle-status');
    Route::post('brands/{brand}/duplicate', [\App\Http\Controllers\Staff\StaffBrandController::class, 'duplicate'])
        ->name('brands.duplicate');
    
    // Brand management routes with route model binding
    Route::resource('brands', \App\Http\Controllers\Staff\StaffBrandController::class);
    
    // Pricing management routes
    Route::get('pricing', [\App\Http\Controllers\Staff\StaffPricingController::class, 'index'])
        ->name('pricing.index');
    Route::get('pricing/{product}/edit', [\App\Http\Controllers\Staff\StaffPricingController::class, 'edit'])
        ->name('pricing.edit');
    Route::put('pricing/{product}', [\App\Http\Controllers\Staff\StaffPricingController::class, 'update'])
        ->name('pricing.update');
    Route::post('pricing/bulk-edit', [\App\Http\Controllers\Staff\StaffPricingController::class, 'bulkEdit'])
        ->name('pricing.bulk-edit');
    Route::post('pricing/bulk-update', [\App\Http\Controllers\Staff\StaffPricingController::class, 'bulkUpdate'])
        ->name('pricing.bulk-update');
    Route::put('pricing/{product}/variant/{variant}', [\App\Http\Controllers\Staff\StaffPricingController::class, 'updateVariant'])
        ->name('pricing.variant.update');
    
    // Promotion management routes
    Route::resource('promotions', \App\Http\Controllers\Staff\StaffPromotionController::class);
    Route::patch('promotions/{promotion}/toggle-status', [\App\Http\Controllers\Staff\StaffPromotionController::class, 'toggleStatus'])
        ->name('promotions.toggle-status');
    Route::post('promotions/{promotion}/duplicate', [\App\Http\Controllers\Staff\StaffPromotionController::class, 'duplicate'])
        ->name('promotions.duplicate');
    Route::get('promotions/{promotion}/affected-products', [\App\Http\Controllers\Staff\StaffPromotionController::class, 'affectedProducts'])
        ->name('promotions.affected-products');
    
    // Help and documentation routes
    Route::get('help/quick-reference', function () {
        return view('staff.help.quick-reference');
    })->name('help.quick-reference');
});
