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
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->text('message');
            $table->string('requested_quantity')->nullable();
            $table->enum('status', ['new', 'contacted', 'quoted', 'closed'])->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
