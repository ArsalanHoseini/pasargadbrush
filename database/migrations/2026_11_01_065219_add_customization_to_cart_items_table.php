<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A cart can now hold the same variant several times with different
     * customer dimensions, so (cart_id, product_variant_id) is no longer
     * unique. cart_id gets its own index first — MySQL needs one for the
     * foreign key once the composite unique index is gone.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('cart_id');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_variant_id']);
            $table->json('customization')->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('customization');
            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['cart_id']);
        });
    }
};
