<?php

namespace App\Support;

/**
 * The company as every page, the JSON-LD graph and the llms.txt files
 * describe it: fixed facts from config/site.php, contact details and social
 * links from the admin's settings (so the owner can change them without a
 * deploy). Empty settings are dropped, never filled with a placeholder.
 */
class SiteProfile
{
    public static function name(): string
    {
        return setting('site_name') ?: config('site.name');
    }

    public static function legalName(): string
    {
        return setting('company_legal_name') ?: config('site.legal_name');
    }

    public static function description(string $locale = 'fa'): string
    {
        return config("site.description.{$locale}");
    }

    /**
     * The site root, without a trailing slash: APP_URL as generated links use it.
     */
    public static function url(): string
    {
        return rtrim(url('/'), '/');
    }

    public static function logoUrl(): string
    {
        return asset(config('site.logo'));
    }

    /**
     * @return array{mobile: ?string, landline: ?string, whatsapp: ?string, whatsapp_url: ?string, bale: ?string, email: ?string, address: ?string, map_url: ?string}
     */
    public static function contact(): array
    {
        return [
            'mobile' => setting('contact_mobile') ?: null,
            'landline' => setting('contact_landline') ?: null,
            'whatsapp' => setting('contact_whatsapp') ?: null,
            'whatsapp_url' => whatsapp_url(setting('contact_whatsapp')),
            'bale' => setting('contact_bale') ?: null,
            'email' => setting('contact_email') ?: null,
            'address' => setting('contact_address') ?: null,
            'map_url' => setting('map_link_url') ?: null,
        ];
    }

    /**
     * The company's own profiles elsewhere (schema.org «sameAs»), keyed by the
     * label the footer shows.
     *
     * @return array<string, string>
     */
    public static function socialLinks(): array
    {
        return array_filter([
            'اینستاگرام' => setting('social_instagram_url'),
            'تلگرام' => setting('social_telegram_url'),
            'لینکدین' => setting('social_linkedin_url'),
            'یوتیوب' => setting('social_youtube_url'),
        ]);
    }

    /**
     * A phone number in international form (+98…), as schema.org expects.
     */
    public static function internationalPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', PersianDigits::toLatin($phone)) ?? '';

        return str_starts_with($digits, '0') ? '+98'.substr($digits, 1) : '+'.$digits;
    }
}
