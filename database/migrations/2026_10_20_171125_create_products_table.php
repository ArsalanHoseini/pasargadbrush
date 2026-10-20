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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignId('primary_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->enum('type', ['quote', 'priced']);
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('specifications')->nullable();
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'made_to_order'])->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->foreignId('og_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
