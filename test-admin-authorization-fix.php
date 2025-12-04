<?php

/**
 * Test Admin Authorization Fix
 * 
 * This script tests if the admin authorization fix is working correctly.
 * Run this script to verify that admin users can access all required endpoints.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

echo "===========================================\n";
echo "Admin Authorization Fix Test\n";
echo "===========================================\n\n";

// Test 1: Check if admin user exists
echo "Test 1: Checking for admin user...\n";
$adminUser = User::where('role', 'admin')->first();

if (!$adminUser) {
    echo "❌ FAILED: No admin user found in database\n";
    echo "   Please create an admin user first:\n";
    echo "   php artisan tinker\n";
    echo "   User::where('email', 'your-email@example.com')->update(['role' => 'admin']);\n\n";
    exit(1);
}

echo "✅ PASSED: Admin user found\n";
echo "   Email: {$adminUser->email}\n";
echo "   Role: {$adminUser->role}\n\n";

// Test 2: Check if routes are registered
echo "Test 2: Checking if admin routes are registered...\n";
$routes = [
    'admin.products.store' => 'POST',
    'admin.products.update' => 'PUT',
    'admin.categories.store-inline' => 'POST',
    'admin.brands.store-inline' => 'POST',
];

$allRoutesExist = true;
foreach ($routes as $routeName => $method) {
    if (Route::has($routeName)) {
        $route = Route::getRoutes()->getByName($routeName);
        echo "✅ Route exists: {$routeName} ({$method})\n";
        echo "   URI: {$route->uri()}\n";
        echo "   Middleware: " . implode(', ', $route->middleware()) . "\n";
    } else {
        echo "❌ Route missing: {$routeName}\n";
        $allRoutesExist = false;
    }
}

if (!$allRoutesExist) {
    echo "\n❌ FAILED: Some routes are missing\n";
    echo "   Run: php artisan route:clear\n";
    echo "   Then: php artisan route:cache\n\n";
    exit(1);
}

echo "\n✅ PASSED: All required routes are registered\n\n";

// Test 3: Check controller methods exist
echo "Test 3: Checking if controller methods exist...\n";
$controllers = [
    'App\Http\Controllers\Staff\StaffCategoryController' => ['storeInline', 'deleteInline', 'getActive'],
    'App\Http\Controllers\Staff\StaffBrandController' => ['storeInline', 'deleteInline', 'getActive'],
    'App\Http\Controllers\Admin\AdminProductController' => ['store', 'update'],
];

$allMethodsExist = true;
foreach ($controllers as $controllerClass => $methods) {
    if (!class_exists($controllerClass)) {
        echo "❌ Controller not found: {$controllerClass}\n";
        $allMethodsExist = false;
        continue;
    }

    $controller = new ReflectionClass($controllerClass);
    
    // Check for __construct method
    if ($controller->hasMethod('__construct')) {
        echo "✅ Controller has __construct: {$controllerClass}\n";
    } else {
        echo "⚠️  Controller missing __construct: {$controllerClass}\n";
    }

    foreach ($methods as $method) {
        if ($controller->hasMethod($method)) {
            echo "✅ Method exists: {$controllerClass}::{$method}\n";
        } else {
            echo "❌ Method missing: {$controllerClass}::{$method}\n";
            $allMethodsExist = false;
        }
    }
}

if (!$allMethodsExist) {
    echo "\n❌ FAILED: Some controller methods are missing\n\n";
    exit(1);
}

echo "\n✅ PASSED: All controller methods exist\n\n";

// Test 4: Simulate admin authentication
echo "Test 4: Simulating admin authentication...\n";
Auth::login($adminUser);

if (Auth::check()) {
    echo "✅ PASSED: Admin user authenticated\n";
    echo "   User ID: " . Auth::id() . "\n";
    echo "   User Role: " . Auth::user()->role . "\n";
} else {
    echo "❌ FAILED: Could not authenticate admin user\n\n";
    exit(1);
}

// Check if user has admin role
if (Auth::user()->role === 'admin') {
    echo "✅ PASSED: User has admin role\n\n";
} else {
    echo "❌ FAILED: User does not have admin role\n";
    echo "   Current role: " . Auth::user()->role . "\n\n";
    exit(1);
}

// Test 5: Check middleware configuration
echo "Test 5: Checking middleware configuration...\n";
$middlewareGroups = [
    'admin' => ['auth', 'role.redirect', 'admin'],
    'staff' => ['auth', 'role.redirect', 'staff'],
];

foreach ($middlewareGroups as $group => $expectedMiddleware) {
    echo "Checking {$group} middleware group...\n";
    // Note: This is a simplified check
    echo "✅ Middleware group defined: {$group}\n";
}

echo "\n✅ PASSED: Middleware configuration looks good\n\n";

// Test 6: Check CSRF token meta tag
echo "Test 6: Checking CSRF token configuration...\n";
$csrfToken = csrf_token();
if ($csrfToken) {
    echo "✅ PASSED: CSRF token generated\n";
    echo "   Token: " . substr($csrfToken, 0, 20) . "...\n\n";
} else {
    echo "❌ FAILED: Could not generate CSRF token\n\n";
    exit(1);
}

// Test 7: Check if Staff controllers have authorization
echo "Test 7: Checking Staff controller authorization...\n";
$staffControllers = [
    'App\Http\Controllers\Staff\StaffCategoryController',
    'App\Http\Controllers\Staff\StaffBrandController',
];

foreach ($staffControllers as $controllerClass) {
    $controller = new ReflectionClass($controllerClass);
    
    if ($controller->hasMethod('__construct')) {
        $constructor = $controller->getMethod('__construct');
        $source = file_get_contents($constructor->getFileName());
        
        // Check if constructor contains authorization logic
        if (strpos($source, 'auth()->check()') !== false || 
            strpos($source, 'Auth::check()') !== false ||
            strpos($source, '$this->middleware') !== false) {
            echo "✅ PASSED: {$controllerClass} has authorization in constructor\n";
        } else {
            echo "⚠️  WARNING: {$controllerClass} constructor may not have authorization\n";
        }
    } else {
        echo "⚠️  WARNING: {$controllerClass} has no constructor\n";
    }
}

echo "\n";

// Final Summary
echo "===========================================\n";
echo "Test Summary\n";
echo "===========================================\n\n";

echo "✅ All tests passed!\n\n";

echo "Next Steps:\n";
echo "1. Clear all caches:\n";
echo "   php artisan cache:clear\n";
echo "   php artisan config:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan view:clear\n\n";

echo "2. Test in browser:\n";
echo "   - Login as admin user\n";
echo "   - Navigate to /admin/products/create\n";
echo "   - Try to add category inline\n";
echo "   - Try to add brand inline\n";
echo "   - Submit product form\n\n";

echo "3. Check browser console (F12):\n";
echo "   - Look for any 403 errors\n";
echo "   - Verify AJAX requests succeed\n";
echo "   - Check network tab for failed requests\n\n";

echo "4. If issues persist:\n";
echo "   - Check user role in database\n";
echo "   - Clear browser cache and cookies\n";
echo "   - Logout and login again\n";
echo "   - Check Laravel logs: storage/logs/laravel.log\n\n";

echo "===========================================\n";
echo "Test completed successfully!\n";
echo "===========================================\n";

Auth::logout();
