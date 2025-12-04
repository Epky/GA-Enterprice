<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "=== User Role Checker ===\n\n";

// Check if user is authenticated
if (Auth::check()) {
    $user = Auth::user();
    echo "✓ User is authenticated\n";
    echo "  ID: {$user->id}\n";
    echo "  Email: {$user->email}\n";
    echo "  Name: {$user->name}\n";
    echo "  Role: {$user->role}\n";
    echo "  Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n\n";
    
    if ($user->role === 'admin') {
        echo "✓ User has admin role - should have access to /admin/products\n";
    } else {
        echo "✗ User does NOT have admin role\n";
        echo "  Current role: {$user->role}\n";
        echo "  Need to change role to 'admin' to access admin routes\n";
    }
} else {
    echo "✗ No user is currently authenticated\n";
    echo "  Please log in first\n\n";
    
    // Show available users
    echo "Available users in database:\n";
    $users = DB::table('users')->select('id', 'email', 'role', 'is_active')->get();
    foreach ($users as $user) {
        $active = $user->is_active ? '✓' : '✗';
        echo "  {$active} ID: {$user->id} | Email: {$user->email} | Role: {$user->role}\n";
    }
}

echo "\n";
