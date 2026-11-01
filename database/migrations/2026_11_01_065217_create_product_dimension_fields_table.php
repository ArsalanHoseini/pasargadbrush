<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The dimensions a customer must give when ordering a product, each one
     * keyed by the letter that marks it on the product's schematic drawing.
     */
    public function up(): void
    {
        Schema::create('product_dimension_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('label');
            $table->string('unit', 10)->default('mm');
            $table->boolean('is_required')->default(true);
            $table->decimal('min_value', 12, 2)->nullable();
            $table->decimal('max_value', 12, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_dimension_fields');
    }
};
