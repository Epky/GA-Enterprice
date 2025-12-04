<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fix User Admin Role ===\n\n";

// Get email from command line argument
$email = $argv[1] ?? null;

if (!$email) {
    echo "Usage: php fix-user-admin-role.php <email>\n\n";
    echo "Available users:\n";
    $users = DB::table('users')->select('id', 'email', 'role', 'is_active')->get();
    foreach ($users as $user) {
        $active = $user->is_active ? '✓' : '✗';
        echo "  {$active} ID: {$user->id} | Email: {$user->email} | Role: {$user->role}\n";
    }
    echo "\nExample: php fix-user-admin-role.php sel@gmail.com\n";
    exit(1);
}

// Find user by email
$user = DB::table('users')->where('email', $email)->first();

if (!$user) {
    echo "✗ User not found with email: {$email}\n";
    exit(1);
}

echo "Found user:\n";
echo "  ID: {$user->id}\n";
echo "  Email: {$user->email}\n";
echo "  Current Role: {$user->role}\n";
echo "  Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n\n";

if ($user->role === 'admin') {
    echo "✓ User already has admin role!\n";
    echo "  You should be able to access /admin/products\n";
    echo "  Make sure you're logged in with this account.\n";
} else {
    echo "Updating user role to 'admin'...\n";
    DB::table('users')
        ->where('id', $user->id)
        ->update([
            'role' => 'admin',
            'updated_at' => now()
        ]);
    
    echo "✓ User role updated to 'admin'\n";
    echo "  Please log out and log back in for changes to take effect.\n";
}

echo "\n";
