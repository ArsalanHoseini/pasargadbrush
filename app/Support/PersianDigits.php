<?php

namespace App\Support;

class PersianDigits
{
    private const PERSIAN = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const ARABIC = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    /**
     * Persian/Arabic keyboards type their own digits, which never match the
     * Latin digits stored in the database (phones, order numbers, ...).
     */
    public static function toLatin(string $input): string
    {
        return str_replace(self::ARABIC, range(0, 9), str_replace(self::PERSIAN, range(0, 9), $input));
    }

    /**
     * Latin digits written the way Persian text shows them (prices, counts),
     * with the Persian thousands separator («۲٬۴۰۰٬۰۰۰»).
     */
    public static function toPersian(string $input): string
    {
        $input = preg_replace('/(?<=\d),(?=\d{3})/', '٬', $input) ?? $input;

        return str_replace(array_map('strval', range(0, 9)), self::PERSIAN, $input);
    }
}
