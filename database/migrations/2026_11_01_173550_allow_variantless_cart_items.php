<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quote-only products (which have no priced variants) can now be put in
     * the cart and ordered, priced afterwards by staff. So a cart line points
     * at its product directly, and the variant becomes optional.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('cart_id')->constrained('products')->cascadeOnDelete();
        });

        // Correlated subquery: portable across MySQL and the SQLite test database.
        DB::table('cart_items')->update([
            'product_id' => DB::raw('(select product_variants.product_id from product_variants where product_variants.id = cart_items.product_variant_id)'),
        ]);

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations (drops variant-less lines first).
     */
    public function down(): void
    {
        DB::table('cart_items')->whereNull('product_variant_id')->delete();

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
