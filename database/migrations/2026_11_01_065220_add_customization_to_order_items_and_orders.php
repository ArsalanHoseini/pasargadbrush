<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Order lines keep the customer's dimensions as a snapshot, and an order
     * containing any custom-dimension line waits for staff to confirm the
     * final price before it's treated as settled.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->json('customization')->nullable()->after('quantity');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('requires_price_confirmation')->default(false)->after('total_rial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('customization');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('requires_price_confirmation');
        });
    }
};
