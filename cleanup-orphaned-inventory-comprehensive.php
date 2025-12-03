<?php

/**
 * Comprehensive Orphaned Inventory Cleanup Script
 * 
 * This script removes inventory records and movements that reference
 * soft-deleted or non-existent products.
 * 
 * Usage: php cleanup-orphaned-inventory-comprehensive.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=================================================\n";
echo "Orphaned Inventory Comprehensive Cleanup Script\n";
echo "=================================================\n\n";

try {
    DB::beginTransaction();

    // Step 1: Find orphaned inventory records
    echo "Step 1: Analyzing orphaned inventory records...\n";
    
    $orphanedInventory = DB::table('inventory')
        ->leftJoin('products', function($join) {
            $join->on('inventory.product_id', '=', 'products.id')
                 ->whereNull('products.deleted_at');
        })
        ->whereNull('products.id')
        ->select('inventory.*')
        ->get();

    $inventoryCount = $orphanedInventory->count();
    echo "Found {$inventoryCount} orphaned inventory records\n";

    if ($inventoryCount > 0) {
        echo "\nOrphaned Inventory Details:\n";
        foreach ($orphanedInventory as $inv) {
            echo "  - Inventory ID: {$inv->id}, Product ID: {$inv->product_id}, Location: {$inv->location}, Qty: {$inv->quantity_available}\n";
        }
    }

    // Step 2: Find orphaned inventory movements
    echo "\nStep 2: Analyzing orphaned inventory movements...\n";
    
    $orphanedMovements = DB::table('inventory_movements')
        ->leftJoin('products', function($join) {
            $join->on('inventory_movements.product_id', '=', 'products.id')
                 ->whereNull('products.deleted_at');
        })
        ->whereNull('products.id')
        ->select('inventory_movements.*')
        ->get();

    $movementsCount = $orphanedMovements->count();
    echo "Found {$movementsCount} orphaned inventory movement records\n";

    if ($movementsCount > 0) {
        echo "\nOrphaned Movements Summary:\n";
        $movementsByType = $orphanedMovements->groupBy('movement_type');
        foreach ($movementsByType as $type => $movements) {
            echo "  - {$type}: " . $movements->count() . " records\n";
        }
    }

    // Step 3: Calculate total impact
    echo "\n=================================================\n";
    echo "CLEANUP SUMMARY\n";
    echo "=================================================\n";
    echo "Total orphaned inventory records: {$inventoryCount}\n";
    echo "Total orphaned movement records: {$movementsCount}\n";
    echo "Total records to be deleted: " . ($inventoryCount + $movementsCount) . "\n";

    // Ask for confirmation
    echo "\n=================================================\n";
    echo "WARNING: This action cannot be undone!\n";
    echo "=================================================\n";
    echo "Do you want to proceed with cleanup? (yes/no): ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirmation = trim(strtolower($line));
    fclose($handle);

    if ($confirmation !== 'yes') {
        echo "\nCleanup cancelled by user.\n";
        DB::rollBack();
        exit(0);
    }

    // Step 4: Delete orphaned inventory records
    echo "\nStep 4: Deleting orphaned inventory records...\n";
    
    $deletedInventory = DB::table('inventory')
        ->leftJoin('products', function($join) {
            $join->on('inventory.product_id', '=', 'products.id')
                 ->whereNull('products.deleted_at');
        })
        ->whereNull('products.id')
        ->delete();

    echo "Deleted {$deletedInventory} inventory records\n";

    // Step 5: Delete orphaned inventory movements
    echo "\nStep 5: Deleting orphaned inventory movements...\n";
    
    $deletedMovements = DB::table('inventory_movements')
        ->leftJoin('products', function($join) {
            $join->on('inventory_movements.product_id', '=', 'products.id')
                 ->whereNull('products.deleted_at');
        })
        ->whereNull('products.id')
        ->delete();

    echo "Deleted {$deletedMovements} movement records\n";

    // Commit the transaction
    DB::commit();

    echo "\n=================================================\n";
    echo "CLEANUP COMPLETED SUCCESSFULLY\n";
    echo "=================================================\n";
    echo "Total inventory records deleted: {$deletedInventory}\n";
    echo "Total movement records deleted: {$deletedMovements}\n";
    echo "Total records cleaned: " . ($deletedInventory + $deletedMovements) . "\n";
    echo "\nDatabase is now clean!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n=================================================\n";
    echo "ERROR OCCURRED\n";
    echo "=================================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nAll changes have been rolled back.\n";
    exit(1);
}

echo "\nDone!\n";
