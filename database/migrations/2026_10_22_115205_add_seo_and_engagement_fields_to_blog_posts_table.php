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
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('status');
            $table->unsignedInteger('view_count')->default(0)->after('published_at');
            $table->unsignedSmallInteger('reading_time')->nullable()->after('view_count');
            $table->string('focus_keyword')->nullable()->after('canonical_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['featured', 'view_count', 'reading_time', 'focus_keyword']);
        });
    }
};
