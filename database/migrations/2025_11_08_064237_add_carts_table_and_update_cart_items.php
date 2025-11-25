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
        // Create carts table
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Only modify cart_items if it exists
        if (!Schema::hasTable('cart_items')) {
            return;
        }

        // Handle differently for PostgreSQL vs SQLite
        if (DB::getDriverName() === 'pgsql') {
            // Get the foreign key constraint names for cart_items
            $userFkName = DB::select("SELECT constraint_name FROM information_schema.table_constraints 
                WHERE table_name = 'cart_items' AND constraint_type = 'FOREIGN KEY' 
                AND constraint_name LIKE '%user_id%'")[0]->constraint_name ?? null;
            
            $variantFkName = DB::select("SELECT constraint_name FROM information_schema.table_constraints 
                WHERE table_name = 'cart_items' AND constraint_type = 'FOREIGN KEY' 
                AND constraint_name LIKE '%variant_id%'")[0]->constraint_name ?? null;

            // Drop foreign key constraints on cart_items using raw SQL
            if ($userFkName) {
                DB::statement("ALTER TABLE cart_items DROP CONSTRAINT {$userFkName}");
            }
            if ($variantFkName) {
                DB::statement("ALTER TABLE cart_items DROP CONSTRAINT {$variantFkName}");
            }
            
            // Drop old columns from cart_items
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn(['user_id', 'session_id', 'variant_id', 'price_at_addition']);
            });
        } else {
            // For SQLite, check if columns exist before dropping
            if (Schema::hasColumn('cart_items', 'user_id')) {
                // Drop indexes first
                try {
                    Schema::table('cart_items', function (Blueprint $table) {
                        $table->dropIndex(['user_id']);
                        $table->dropIndex(['session_id']);
                    });
                } catch (\Exception $e) {
                    // Indexes might not exist, continue
                }
                
                // Drop foreign keys if they exist
                try {
                    Schema::table('cart_items', function (Blueprint $table) {
                        $table->dropForeign(['user_id']);
                    });
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
                
                try {
                    Schema::table('cart_items', function (Blueprint $table) {
                        $table->dropForeign(['variant_id']);
                    });
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
                
                // Then drop columns
                Schema::table('cart_items', function (Blueprint $table) {
                    $table->dropColumn(['user_id', 'session_id', 'variant_id', 'price_at_addition']);
                });
            }
        }

        // Add new columns to cart_items if they don't exist
        if (!Schema::hasColumn('cart_items', 'cart_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('cart_id')->after('id')->constrained()->onDelete('cascade');
                $table->decimal('price_at_time', 10, 2)->after('quantity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert cart_items table changes
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['cart_id']);
            $table->dropColumn(['cart_id', 'price_at_time']);
            
            // Restore old columns
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable()->after('user_id');
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->onDelete('set null');
            $table->decimal('price_at_addition', 10, 2)->after('quantity');
        });

        // Drop carts table
        Schema::dropIfExists('carts');
    }
};
