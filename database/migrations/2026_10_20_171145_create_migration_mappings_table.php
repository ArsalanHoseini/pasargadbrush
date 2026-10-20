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
        Schema::create('migration_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_system')->default('wordpress');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('source_url')->nullable();
            $table->string('target_url')->nullable();
            $table->enum('status', ['pending', 'imported', 'skipped', 'failed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['source_system', 'source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_mappings');
    }
};
