<?php

namespace App\Support;

class IranianMobile
{
    /**
     * The one canonical shape every mobile number is stored in: 09XXXXXXXXX.
     */
    public const PATTERN = '/^09\d{9}$/';

    /**
     * Reduces every common way a customer types the same mobile number —
     * Persian/Arabic digits, spaces, dashes, parentheses, +98 / 0098 / 98
     * prefixes, or a missing leading zero — to the canonical 09XXXXXXXXX,
     * so one person is always one customer. Returns the cleaned input
     * unchanged when it can't be recognized as a mobile number, leaving it
     * to validation (PATTERN) to reject.
     */
    public static function normalize(string $input): string
    {
        $number = PersianDigits::toLatin($input);
        $number = preg_replace('/[\s\-\(\)\.\x{200C}\x{200F}\x{200E}]+/u', '', $number) ?? $number;

        if (str_starts_with($number, '+98')) {
            $number = '0'.substr($number, 3);
        } elseif (str_starts_with($number, '0098')) {
            $number = '0'.substr($number, 4);
        } elseif (preg_match('/^989\d{9}$/', $number)) {
            $number = '0'.substr($number, 2);
        } elseif (preg_match('/^9\d{9}$/', $number)) {
            $number = '0'.$number;
        }

        return $number;
    }
}
