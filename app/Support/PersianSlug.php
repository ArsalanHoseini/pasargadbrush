<?php

namespace App\Support;

class PersianSlug
{
    /**
     * Laravel's Str::slug() transliterates to ASCII, which mangles Persian
     * text into an empty or meaningless string. This keeps Unicode letters
     * and digits (Persian, Arabic, Latin) and only normalizes whitespace/
     * punctuation into single dashes — matching how the existing WordPress
     * site's Persian slugs are actually shaped (see docs/WORDPRESS_MIGRATION.md).
     */
    public static function make(string $title): string
    {
        $slug = trim($title);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';

        return trim($slug, '-');
    }
}
