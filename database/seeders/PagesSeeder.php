<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    /**
     * Only seeds the "Contact Us" page, whose real content (phone/email/address)
     * comes dynamically from settings — the intro line here is a neutral, true
     * instruction, not fabricated company content. "About Us" is deliberately
     * NOT seeded: that requires real company narrative only the owner can write.
     */
    public function run(): void
    {
        Page::query()->updateOrCreate(
            ['slug' => 'contact-us'],
            [
                'title' => 'تماس با ما',
                'body' => '<p>برای ارتباط با کارشناسان فروش و دریافت مشاوره یا استعلام قیمت، از راه‌های زیر با ما در تماس باشید.</p>',
                'template' => 'contact',
                'status' => 'published',
                'published_at' => now(),
            ],
        );
    }
}
