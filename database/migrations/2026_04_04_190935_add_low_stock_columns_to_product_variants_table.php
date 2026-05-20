<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock_quantity');
            $table->boolean('low_stock_notified')->default(false)->after('low_stock_threshold');
            $table->index('stock_quantity');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'stock_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['stock_quantity']);
            $table->dropColumn(['low_stock_threshold', 'low_stock_notified']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'stock_quantity']);
        });
    }
};
