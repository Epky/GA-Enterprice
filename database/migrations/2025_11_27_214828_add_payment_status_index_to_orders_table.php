<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds payment_status index to orders table for analytics performance.
     */
    public function up(): void
    {
        // Use raw SQL with IF NOT EXISTS for PostgreSQL compatibility
        DB::statement("CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS idx_orders_payment_status");
    }
};
