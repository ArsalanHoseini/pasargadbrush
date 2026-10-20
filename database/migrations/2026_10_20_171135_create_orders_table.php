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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'failed', 'refunded'])->default('unpaid');
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->unsignedBigInteger('subtotal_rial');
            $table->unsignedBigInteger('shipping_cost_rial')->default(0);
            $table->unsignedBigInteger('total_rial');
            $table->text('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
