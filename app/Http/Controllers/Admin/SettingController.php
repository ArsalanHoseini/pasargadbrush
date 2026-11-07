<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * key => [label, group, validation rule]. The admin fills these in — none
     * are pre-seeded with fabricated values (social links start empty).
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const FIELDS = [
        'site_name' => ['نام سایت', 'عمومی', 'nullable|string|max:255'],
        'company_legal_name' => ['نام رسمی شرکت', 'عمومی', 'nullable|string|max:255'],
        'contact_mobile' => ['موبایل', 'تماس', 'nullable|string|max:50'],
        'contact_landline' => ['تلفن ثابت', 'تماس', 'nullable|string|max:50'],
        'contact_email' => ['ایمیل', 'تماس', 'nullable|email|max:255'],
        'contact_address' => ['آدرس', 'تماس', 'nullable|string|max:500'],
        'contact_whatsapp' => ['واتساپ (شماره موبایل)', 'تماس', 'nullable|string|max:50'],
        'contact_bale' => ['بله (شماره موبایل)', 'تماس', 'nullable|string|max:50'],
        'map_embed_url' => ['نشانی embed نقشه گوگل (src داخل iframe)', 'نقشه', 'nullable|url|starts_with:https://www.google.com/maps/embed|max:2000'],
        'map_link_url' => ['لینک باز شدن موقعیت در گوگل‌مپ', 'نقشه', 'nullable|url|max:500'],
        'social_instagram_url' => ['اینستاگرام', 'شبکه‌های اجتماعی', 'nullable|url|max:255'],
        'social_telegram_url' => ['تلگرام', 'شبکه‌های اجتماعی', 'nullable|url|max:255'],
        'social_linkedin_url' => ['لینکدین', 'شبکه‌های اجتماعی', 'nullable|url|max:255'],
        'social_youtube_url' => ['یوتیوب', 'شبکه‌های اجتماعی', 'nullable|url|max:255'],
    ];

    public function edit(): View
    {
        $values = Setting::query()->pluck('value', 'key');

        $groups = [];
        foreach (self::FIELDS as $key => [$label, $group]) {
            $groups[$group][$key] = ['label' => $label, 'value' => $values[$key] ?? ''];
        }

        return view('admin.settings.edit', ['groups' => $groups]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::FIELDS as $key => [$label, $group, $rule]) {
            $rules[$key] = $rule;
        }

        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value !== null ? trim($value) : null, 'group' => self::FIELDS[$key][1]],
            );
        }

        return redirect()->route('admin.settings.edit')->with('status', 'تنظیمات ذخیره شد.');
    }
}
