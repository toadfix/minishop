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
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->string('type', 20)->default('flat_rate')->after('sort_order');
            $table->string('carrier', 50)->nullable()->after('type');
            $table->string('service_code', 50)->nullable()->after('carrier');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['type', 'carrier', 'service_code']);
        });
    }
};
