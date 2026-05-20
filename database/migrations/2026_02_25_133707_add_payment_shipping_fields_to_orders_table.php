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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->after('coupon_id')->constrained()->nullOnDelete();
            $table->string('payment_gateway')->nullable()->after('status');
            $table->string('payment_intent_id')->nullable()->index()->after('payment_gateway');
            $table->string('payment_status')->default('pending')->after('payment_intent_id');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
            $table->dropIndex(['payment_intent_id']);
            $table->dropColumn(['shipping_method_id', 'payment_gateway', 'payment_intent_id', 'payment_status', 'paid_at']);
        });
    }
};
