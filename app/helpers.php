<?php

use App\Models\Setting;
use App\Support\IranianMobile;
use App\Support\PersianDate;

if (! function_exists('setting')) {
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('whatsapp_url')) {
    /**
     * wa.me chat link for an Iranian mobile in any common spelling; null when empty.
     */
    function whatsapp_url(?string $mobile): ?string
    {
        if (blank($mobile)) {
            return null;
        }

        return 'https://wa.me/98'.substr(IranianMobile::normalize($mobile), 1);
    }
}

if (! function_exists('persian_date')) {
    /**
     * A date as people on this site read it: Persian (Solar Hijri) calendar,
     * Tehran time, Persian digits. Empty for null. Machine-facing dates (RSS,
     * sitemap, datetime attributes, order numbers) stay Gregorian/ISO.
     *
     * @param  string  $pattern  One of the PersianDate::* constants, or any ICU pattern.
     */
    function persian_date(?DateTimeInterface $date, string $pattern = PersianDate::DATETIME): string
    {
        return PersianDate::format($date, $pattern);
    }
}
