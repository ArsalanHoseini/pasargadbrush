<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A message thread between staff and one customer: either about a
     * specific order or quote request (at most one thread each), or a
     * general support ticket (neither set).
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->unique()->constrained('orders')->nullOnDelete();
            $table->foreignId('quote_request_id')->nullable()->unique()->constrained('quote_requests')->nullOnDelete();
            $table->string('subject');
            $table->enum('status', ['open', 'answered', 'closed'])->default('open');
            $table->boolean('customer_unread')->default(false);
            $table->boolean('staff_unread')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
