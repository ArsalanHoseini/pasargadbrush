<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The site's editable menus (header and footer «quick links»), two levels
     * deep. Seeded with the links that were hard-coded until now, so the site
     * looks the same until someone edits them in /admin/menus.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('location', 20);
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('type', 20)->default('link');
            $table->string('url', 2048)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['location', 'parent_id', 'sort_order']);
        });

        $now = now();
        $defaults = [
            'header' => [
                ['صفحه اصلی', 'link', '/'],
                ['محصولات', 'categories', null],
                ['مقالات', 'link', '/blog'],
                ['درباره ما', 'link', '/page/about-us'],
                ['تماس با ما', 'link', '/page/contact-us'],
            ],
            'footer' => [
                ['صفحه اصلی', 'link', '/'],
                ['مقالات', 'link', '/blog'],
                ['درباره ما', 'link', '/page/about-us'],
                ['تماس با ما', 'link', '/page/contact-us'],
                ['پیگیری سفارش', 'link', '/order-tracking'],
            ],
        ];

        foreach ($defaults as $location => $items) {
            foreach ($items as $position => [$label, $type, $url]) {
                DB::table('menu_items')->insert([
                    'location' => $location,
                    'label' => $label,
                    'type' => $type,
                    'url' => $url,
                    'sort_order' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
