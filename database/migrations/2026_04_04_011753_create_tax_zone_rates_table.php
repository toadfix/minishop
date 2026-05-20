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
        Schema::create('tax_zone_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_zone_id')->constrained('tax_zones')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('name_fr', 50)->nullable();
            $table->decimal('rate', 8, 4);
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_shipping_taxable')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tax_zone_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_zone_rates');
    }
};
