<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orders can now be entered by staff (e.g. after a phone call or from a
     * quote request) as well as placed on the website; record which, and by
     * whom. A quote request remembers the order it turned into.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('website')->after('order_number');
            $table->foreignId('created_by_user_id')->nullable()->after('source')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('status')
                ->constrained('orders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn('source');
        });
    }
};
