<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlCalendar;
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

    /**
     * Reads a Persian (Solar Hijri) date typed by staff — «۱۴۰۵/۰۸/۰۹»,
     * «1405-8-9» — as the start of that day in Tehran. Anything that is not a
     * real date gives null, so a typo simply leaves the filter off.
     */
    public static function parse(?string $value): ?DateTimeImmutable
    {
        if (! preg_match('/^\s*(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})\s*$/', PersianDigits::toLatin((string) $value), $parts)) {
            return null;
        }

        [, $year, $month, $day] = array_map('intval', $parts);

        if ($month < 1 || $month > 12 || $day < 1 || $day > ($month <= 6 ? 31 : 30)) {
            return null;
        }

        $calendar = IntlCalendar::createInstance('Asia/Tehran', 'fa_IR@calendar=persian');
        $calendar->clear();
        $calendar->setDateTime($year, $month - 1, $day, 0, 0, 0);

        // Esfand has 29 days outside leap years; ICU would roll the 30th into Farvardin.
        if ($calendar->get(IntlCalendar::FIELD_DAY_OF_MONTH) !== $day) {
            return null;
        }

        return (new DateTimeImmutable('@'.intdiv((int) $calendar->getTime(), 1000)))->setTimezone(new DateTimeZone('Asia/Tehran'));
    }
}
