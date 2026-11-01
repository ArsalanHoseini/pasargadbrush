<?php

namespace App\Support;

use DateTimeInterface;
use IntlDateFormatter;

class PersianDate
{
    /** «۹ آبان ۱۴۰۵، ۱۷:۳۵» — customer-facing date and time. */
    public const DATETIME = 'd MMMM y، HH:mm';

    /** «۹ آبان ۱۴۰۵» — article and review dates. */
    public const DATE = 'd MMMM y';

    /** «۱۴۰۵/۰۸/۰۹ ۱۷:۳۵» — compact, sortable-looking, for admin tables. */
    public const COMPACT = 'yyyy/MM/dd HH:mm';

    /** «۱۴۰۵/۰۸/۰۹» */
    public const COMPACT_DATE = 'yyyy/MM/dd';

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
