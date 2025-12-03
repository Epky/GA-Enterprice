<?php

/**
 * Automatically clean up orphaned inventory records (inventory without valid products)
 * This version runs without user confirmation
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

echo "Checking for orphaned inventory records...\n\n";

// Find inventory records without valid products
$orphanedInventory = Inventory::whereDoesntHave('product')->get();

$count = $orphanedInventory->count();

if ($count === 0) {
    echo "✓ No orphaned inventory records found. Database is clean!\n";
    exit(0);
}

echo "Found {$count} orphaned inventory record(s):\n\n";

foreach ($orphanedInventory as $inventory) {
    echo "  - Inventory ID: {$inventory->id}, Product ID: {$inventory->product_id}, Location: {$inventory->location}\n";
    echo "    Quantity Available: {$inventory->quantity_available}, Reserved: {$inventory->quantity_reserved}\n";
}

echo "\nDeleting orphaned inventory records...\n";

DB::transaction(function () use ($orphanedInventory) {
    foreach ($orphanedInventory as $inventory) {
        $inventory->delete();
        echo "  ✓ Deleted inventory ID: {$inventory->id}\n";
    }
});

echo "\n✓ Successfully deleted {$count} orphaned inventory record(s).\n";
echo "Your database is now clean!\n";
