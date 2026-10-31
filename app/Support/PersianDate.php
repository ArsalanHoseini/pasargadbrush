<?php

namespace App\Support;

use DateTimeInterface;
use IntlDateFormatter;

class PersianDate
{
    /**
     * Formats a date in the Persian (Solar Hijri) calendar, Tehran time, with
     * Persian digits — e.g. «۹ آبان ۱۴۰۵، ۱۷:۳۵». Uses PHP's built-in intl
     * extension (ICU), so no extra package is needed.
     *
     * @param  string  $pattern  ICU date pattern, see https://unicode-org.github.io/icu/userguide/format_parse/datetime/
     */
    public static function format(?DateTimeInterface $date, string $pattern = 'd MMMM y، HH:mm'): string
    {
        if (! $date) {
            return '';
        }

        $formatter = new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL,
            $pattern,
        );

        return (string) $formatter->format($date);
    }
}
