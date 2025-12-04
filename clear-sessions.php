<?php

/**
 * Clear all sessions to force re-login
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "===========================================\n";
echo "Session Clearer\n";
echo "===========================================\n\n";

try {
    // Clear all sessions
    DB::table('sessions')->truncate();
    echo "✅ All sessions cleared!\n\n";
    
    // Clear application cache
    Artisan::call('cache:clear');
    echo "✅ Application cache cleared!\n\n";
    
    // Clear config cache
    Artisan::call('config:clear');
    echo "✅ Config cache cleared!\n\n";
    
    echo "===========================================\n";
    echo "✅ Done! Please follow these steps:\n";
    echo "===========================================\n";
    echo "1. Close all browser windows\n";
    echo "2. Clear your browser cache and cookies\n";
    echo "3. Open a new browser window\n";
    echo "4. Go to your application login page\n";
    echo "5. Log in with an admin account:\n";
    echo "   - Email: admin@admin.com\n";
    echo "   - Password: admin\n";
    echo "   OR\n";
    echo "   - Email: sel@admin.com\n";
    echo "   - Password: (your password)\n";
    echo "6. Try accessing /admin/products again\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
