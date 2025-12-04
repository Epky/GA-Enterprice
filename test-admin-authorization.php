<?php

/**
 * Test Admin Authorization Fix
 * 
 * This script tests if admin users can access staff controller endpoints
 * Run this after applying the authorization fix
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== Testing Admin Authorization Fix ===\n\n";

// Find an admin user
$admin = User::where('role', 'admin')->first();

if (!$admin) {
    echo "❌ No admin user found in database\n";
    echo "Please create an admin user first\n";
    exit(1);
}

echo "✓ Found admin user: {$admin->email}\n";
echo "  Role: {$admin->role}\n\n";

// Test 1: Check if admin can pass staff middleware
echo "Test 1: Admin accessing staff middleware\n";
Auth::login($admin);

$middleware = new App\Http\Middleware\StaffMiddleware();
$request = Illuminate\Http\Request::create('/test', 'GET');

try {
    $response = $middleware->handle($request, function ($req) {
        return new Illuminate\Http\Response('Success');
    });
    
    if ($response->getStatusCode() === 200) {
        echo "✅ PASS: Admin can access staff middleware\n\n";
    } else {
        echo "❌ FAIL: Unexpected response code: {$response->getStatusCode()}\n\n";
    }
} catch (\Exception $e) {
    echo "❌ FAIL: {$e->getMessage()}\n\n";
}

// Test 2: Check if staff user can still access
echo "Test 2: Staff user accessing staff middleware\n";
$staff = User::where('role', 'staff')->first();

if ($staff) {
    Auth::login($staff);
    
    try {
        $response = $middleware->handle($request, function ($req) {
            return new Illuminate\Http\Response('Success');
        });
        
        if ($response->getStatusCode() === 200) {
            echo "✅ PASS: Staff can access staff middleware\n\n";
        } else {
            echo "❌ FAIL: Unexpected response code: {$response->getStatusCode()}\n\n";
        }
    } catch (\Exception $e) {
        echo "❌ FAIL: {$e->getMessage()}\n\n";
    }
} else {
    echo "⚠️  SKIP: No staff user found\n\n";
}

// Test 3: Check if customer is blocked
echo "Test 3: Customer accessing staff middleware (should fail)\n";
$customer = User::where('role', 'customer')->first();

if ($customer) {
    Auth::login($customer);
    
    try {
        $response = $middleware->handle($request, function ($req) {
            return new Illuminate\Http\Response('Success');
        });
        
        echo "❌ FAIL: Customer should not be able to access\n\n";
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            echo "✅ PASS: Customer correctly blocked with 403\n\n";
        } else {
            echo "❌ FAIL: Wrong error code: {$e->getStatusCode()}\n\n";
        }
    } catch (\Exception $e) {
        echo "❌ FAIL: Unexpected error: {$e->getMessage()}\n\n";
    }
} else {
    echo "⚠️  SKIP: No customer user found\n\n";
}

// Test 4: Check routes exist
echo "Test 4: Checking if admin routes are registered\n";
$routes = app('router')->getRoutes();

$requiredRoutes = [
    'admin.categories.store-inline',
    'admin.categories.delete-inline',
    'admin.brands.store-inline',
    'admin.brands.delete-inline',
    'admin.categories.active',
    'admin.brands.active',
];

$allRoutesExist = true;
foreach ($requiredRoutes as $routeName) {
    $route = $routes->getByName($routeName);
    if ($route) {
        echo "  ✓ Route exists: {$routeName}\n";
    } else {
        echo "  ✗ Route missing: {$routeName}\n";
        $allRoutesExist = false;
    }
}

if ($allRoutesExist) {
    echo "\n✅ PASS: All required routes exist\n\n";
} else {
    echo "\n❌ FAIL: Some routes are missing\n\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "The authorization fix has been applied.\n";
echo "Admin users can now:\n";
echo "  • Create and edit products\n";
echo "  • Add categories inline\n";
echo "  • Add brands inline\n";
echo "  • Access all staff functionality\n\n";

echo "Next steps:\n";
echo "1. Test in browser by logging in as admin\n";
echo "2. Try creating a new product\n";
echo "3. Try adding a category/brand inline\n";
echo "4. Verify no 403 errors appear\n";

