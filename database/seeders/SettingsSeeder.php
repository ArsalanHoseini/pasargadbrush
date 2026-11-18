<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Only real, owner-confirmed values (2026-10-21, contact/social/map updated 2026-11-07)
     * — never placeholder/fabricated data. Unconfirmed social links stay unset.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => ['value' => 'پاسارگاد برس', 'group' => 'general'],
            'company_legal_name' => ['value' => 'شرکت برس صنعت پاسارگاد', 'group' => 'general'],
            'contact_mobile' => ['value' => '09122787949', 'group' => 'contact'],
            'contact_whatsapp' => ['value' => '09122787949', 'group' => 'contact'],
            'contact_bale' => ['value' => '09174287445', 'group' => 'contact'],
            'contact_email' => ['value' => 'pbc_info@yahoo.com', 'group' => 'contact'],
            'contact_address' => ['value' => 'تخت جمشید، کیلومتر ۶۰ جاده شیراز-اصفهان، شهرک صنعتی انجیره مرودشت', 'group' => 'contact'],
            'social_instagram_url' => ['value' => 'https://www.instagram.com/pasargad_brush_co', 'group' => 'social'],
            'map_embed_url' => ['value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5404.938958324429!2d52.99177069147676!3d29.928609542173806!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3fade7e980f8d741%3A0x70fa3dd2f887dd07!2z2KjYsdizINi12YbYudiqINm-2KfYs9in2LHar9in2K8!5e0!3m2!1sfa!2s!4v1794064165311!5m2!1sfa!2s', 'group' => 'map'],
            'map_link_url' => ['value' => 'https://maps.google.com/?cid=8140887252747672839', 'group' => 'map'],
        ];

        foreach ($settings as $key => $attributes) {
            Setting::query()->updateOrCreate(['key' => $key], $attributes);
        }
    }
}
