<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For both PostgreSQL and SQLite, we need to recreate the table
        // because Laravel's enum implementation uses CHECK constraints
        
        // Create a temporary table with the new structure
        Schema::create('inventory_movements_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants');
            $table->enum('movement_type', ['purchase', 'sale', 'return', 'adjustment', 'damage', 'transfer', 'reservation', 'release']);
            $table->integer('quantity');
            $table->string('location_from', 100)->nullable();
            $table->string('location_to', 100)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->bigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('product_id');
            $table->index('movement_type');
            $table->index('created_at');
        });
        
        // Copy data from old table to new table
        DB::statement('INSERT INTO inventory_movements_temp SELECT * FROM inventory_movements');
        
        // Drop old table
        Schema::dropIfExists('inventory_movements');
        
        // Rename temp table to original name
        Schema::rename('inventory_movements_temp', 'inventory_movements');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be easily reversed as it would require
        // removing enum values which may be in use
        // For development/testing, you can refresh the database
    }
};
