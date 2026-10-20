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
        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->smallInteger('status_code')->default(301);
            $table->unsignedInteger('hits_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_redirects');
    }
};
