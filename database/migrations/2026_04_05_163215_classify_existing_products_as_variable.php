<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Classify existing products that have variants as 'variable'.
     */
    public function up(): void
    {
        DB::table('products')
            ->whereExists(
                fn ($query) => $query->selectRaw('1')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id')
            )
            ->update(['type' => 'variable']);
    }

    /**
     * Reset all products back to 'simple' (the column default).
     */
    public function down(): void
    {
        DB::table('products')
            ->where('type', 'variable')
            ->update(['type' => 'simple']);
    }
};
