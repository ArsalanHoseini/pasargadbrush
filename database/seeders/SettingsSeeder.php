<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Only real, owner-confirmed values (2026-10-21) — never placeholder/fabricated data.
     * Social links are intentionally left unset until real URLs are confirmed.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => ['value' => 'پاسارگاد برس', 'group' => 'general'],
            'company_legal_name' => ['value' => 'شرکت برس صنعتی پاسارگاد', 'group' => 'general'],
            'contact_mobile' => ['value' => '09174287445', 'group' => 'contact'],
            'contact_email' => ['value' => 'pbc_info@yahoo.com', 'group' => 'contact'],
            'contact_address' => ['value' => 'مرودشت، کیلومتر ۶۰ جاده شیراز-اصفهان، شهرک صنعتی انجیره مرودشت', 'group' => 'contact'],
        ];

        foreach ($settings as $key => $attributes) {
            Setting::query()->updateOrCreate(['key' => $key], $attributes);
        }
    }
}
