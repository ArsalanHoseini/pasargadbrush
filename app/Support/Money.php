<?php

namespace App\Support;

class Money
{
    /**
     * All prices are stored in Rial (see docs/DATABASE_DESIGN.md). Toman is
     * display-only — this is the one place that conversion happens.
     */
    public static function toman(int $rial): string
    {
        return number_format(intdiv($rial, 10)).' تومان';
    }
}
