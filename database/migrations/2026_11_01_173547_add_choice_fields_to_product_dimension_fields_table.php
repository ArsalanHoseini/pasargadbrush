<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A product's order form now has two kinds of fields: numeric dimensions
     * (as before) and choices from a fixed list, e.g. «جنس الیاف»: PA / PET / PP.
     */
    public function up(): void
    {
        Schema::table('product_dimension_fields', function (Blueprint $table) {
            $table->string('type', 10)->default('number')->after('product_id');
            $table->json('options')->nullable()->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_dimension_fields', function (Blueprint $table) {
            $table->dropColumn(['type', 'options']);
        });
    }
};
