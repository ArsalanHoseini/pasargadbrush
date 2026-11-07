<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data-only migration for the owner's correction list of 2026-11-07, so the
 * same changes land on every existing database (local and production) with a
 * plain `php artisan migrate`:
 *  - the company's real name is «برس صنعت پاسارگاد», not «برس صنعتی پاسارگاد»
 *    (fixed in settings and in every imported text: products, posts, pages, image alts);
 *  - every public phone number is now 09122787949 (also WhatsApp); Bale stays 09174287445;
 *  - the address starts with «تخت جمشید»;
 *  - real Instagram link and Google Maps location.
 *
 * A fresh install (no settings rows yet) is left to SettingsSeeder.
 */
return new class extends Migration
{
    private const WRONG_NAME = 'برس صنعتی پاسارگاد';

    private const RIGHT_NAME = 'برس صنعت پاسارگاد';

    /**
     * @var array<string, array<int, string>>
     */
    private const TEXT_COLUMNS = [
        'products' => ['name', 'short_description', 'description', 'specifications', 'meta_title', 'meta_description'],
        'blog_posts' => ['title', 'excerpt', 'body', 'meta_title', 'meta_description'],
        'pages' => ['title', 'body', 'meta_title', 'meta_description'],
        'media' => ['alt_text'],
    ];

    public function up(): void
    {
        foreach (self::TEXT_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->where($column, 'like', '%'.self::WRONG_NAME.'%')
                    ->update([$column => DB::raw('REPLACE(`'.$column.'`, '.DB::getPdo()->quote(self::WRONG_NAME).', '.DB::getPdo()->quote(self::RIGHT_NAME).')')]);
            }
        }

        if (DB::table('settings')->doesntExist()) {
            return;
        }

        $settings = [
            'company_legal_name' => ['شرکت برس صنعت پاسارگاد', 'general'],
            'contact_mobile' => ['09122787949', 'contact'],
            'contact_whatsapp' => ['09122787949', 'contact'],
            'contact_bale' => ['09174287445', 'contact'],
            'contact_address' => ['تخت جمشید، کیلومتر ۶۰ جاده شیراز-اصفهان، شهرک صنعتی انجیره مرودشت', 'contact'],
            'social_instagram_url' => ['https://www.instagram.com/pasargad_brush_co', 'social'],
            'map_embed_url' => ['https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5404.938958324429!2d52.99177069147676!3d29.928609542173806!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3fade7e980f8d741%3A0x70fa3dd2f887dd07!2z2KjYsdizINi12YbYudiqINm-2KfYs9in2LHar9in2K8!5e0!3m2!1sfa!2s!4v1794064165311!5m2!1sfa!2s', 'map'],
            'map_link_url' => ['https://maps.google.com/?cid=8140887252747672839', 'map'],
        ];

        foreach ($settings as $key => [$value, $group]) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'updated_at' => now()],
            );
        }

        Cache::forget('settings');
    }

    public function down(): void
    {
        // Data correction only — nothing to undo.
    }
};
