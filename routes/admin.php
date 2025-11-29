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
});
