<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two kinds of address rows: ones in the customer's address book
     * (is_saved = true, freely editable) and the frozen copy every order
     * points at (is_saved = false) — so editing or deleting an address-book
     * entry never changes where a past order was shipped.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->boolean('is_saved')->default(false)->after('customer_id');
            $table->string('label')->nullable()->after('is_saved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['is_saved', 'label']);
        });
    }
};
