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
        Schema::create('tax_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('country_code', 2)->nullable();
            $table->char('province_code', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->smallInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['country_code', 'province_code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_zones');
    }
};
