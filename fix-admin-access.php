<?php

/**
 * Fix Admin Access - Diagnostic and Repair Script
 * 
 * This script checks and fixes admin access issues by:
 * 1. Checking if the logged-in user exists
 * 2. Verifying the user's role
 * 3. Updating the role to 'admin' if needed
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "===========================================\n";
echo "Admin Access Diagnostic & Repair Tool\n";
echo "===========================================\n\n";

// Check all users and their roles
echo "📋 Current Users in Database:\n";
echo "-------------------------------------------\n";

$users = User::all();

if ($users->isEmpty()) {
    echo "❌ No users found in database!\n\n";
    echo "Creating default admin user...\n";
    
    $admin = User::create([
        'name' => 'Administrator',
        'email' => 'admin@admin.com',
        'password' => Hash::make('admin'),
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    
    echo "✅ Admin user created successfully!\n";
    echo "   Email: admin@admin.com\n";
    echo "   Password: admin\n";
    echo "   Role: admin\n\n";
} else {
    foreach ($users as $user) {
        $roleIcon = match($user->role) {
            'admin' => '👑',
            'staff' => '👔',
            'customer' => '👤',
            default => '❓'
        };
        
        $statusIcon = $user->is_active ? '✅' : '❌';
        
        echo "{$roleIcon} ID: {$user->id} | Email: {$user->email}\n";
        echo "   Name: {$user->name}\n";
        echo "   Role: {$user->role}\n";
        echo "   Active: {$statusIcon}\n";
        echo "-------------------------------------------\n";
    }
}

echo "\n";

// Ask which user to make admin
echo "🔧 Fix Admin Access\n";
echo "-------------------------------------------\n";
echo "Enter the email of the user you want to grant admin access to\n";
echo "(or press Enter to skip): ";

$handle = fopen("php://stdin", "r");
$email = trim(fgets($handle));
fclose($handle);

if (!empty($email)) {
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        echo "❌ User with email '{$email}' not found!\n";
        exit(1);
    }
    
    echo "\n📝 Current user details:\n";
    echo "   Name: {$user->name}\n";
    echo "   Email: {$user->email}\n";
    echo "   Current Role: {$user->role}\n";
    echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n\n";
    
    if ($user->role === 'admin' && $user->is_active) {
        echo "✅ User already has admin access and is active!\n";
        echo "   You should be able to access /admin/products now.\n";
        exit(0);
    }
    
    echo "🔄 Updating user to admin...\n";
    
    $user->role = 'admin';
    $user->is_active = true;
    $user->save();
    
    echo "✅ User updated successfully!\n";
    echo "   New Role: {$user->role}\n";
    echo "   Active: Yes\n\n";
    echo "🎉 You can now access /admin/products!\n";
    echo "   Please log out and log back in for changes to take effect.\n";
} else {
    echo "⏭️  Skipped. No changes made.\n";
}

echo "\n===========================================\n";
echo "Done!\n";
echo "===========================================\n";
