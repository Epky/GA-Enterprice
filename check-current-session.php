<?php

/**
 * Check Current Session - See who is logged in
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "Session Checker\n";
echo "===========================================\n\n";

echo "📋 Active Sessions:\n";
echo "-------------------------------------------\n";

try {
    $sessions = DB::table('sessions')
        ->orderBy('last_activity', 'desc')
        ->get();
    
    if ($sessions->isEmpty()) {
        echo "❌ No active sessions found.\n";
        echo "   Please log in to the application.\n";
    } else {
        foreach ($sessions as $session) {
            $userId = $session->user_id ?? 'Guest';
            $lastActivity = date('Y-m-d H:i:s', $session->last_activity);
            
            echo "Session ID: " . substr($session->id, 0, 20) . "...\n";
            echo "User ID: {$userId}\n";
            echo "Last Activity: {$lastActivity}\n";
            
            if ($userId !== 'Guest') {
                $user = DB::table('users')->where('id', $userId)->first();
                if ($user) {
                    echo "User Email: {$user->email}\n";
                    echo "User Role: {$user->role}\n";
                    echo "User Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
                }
            }
            
            echo "-------------------------------------------\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error checking sessions: " . $e->getMessage() . "\n";
}

echo "\n===========================================\n";
echo "💡 Solutions:\n";
echo "===========================================\n";
echo "1. Clear your browser cache and cookies\n";
echo "2. Log out and log back in\n";
echo "3. Use one of these admin accounts:\n";
echo "   - admin@admin.com (password: admin)\n";
echo "   - sel@admin.com\n";
echo "\n";
