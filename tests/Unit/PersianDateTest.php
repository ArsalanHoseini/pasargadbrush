<?php

namespace Tests\Unit;

use App\Support\PersianDate;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class PersianDateTest extends TestCase
{
    public function test_formats_in_the_persian_calendar_in_tehran_time(): void
    {
        $utc = new DateTimeImmutable('2026-10-31 14:05:00', new DateTimeZone('UTC'));

        $this->assertSame('۹ آبان ۱۴۰۵، ۱۷:۳۵', PersianDate::format($utc));
    }

    public function test_accepts_a_custom_pattern(): void
    {
        $utc = new DateTimeImmutable('2026-03-21 00:00:00', new DateTimeZone('UTC'));

        $this->assertSame('۱۴۰۵/۰۱/۰۱', PersianDate::format($utc, 'yyyy/MM/dd'));
    }

    public function test_null_formats_as_an_empty_string(): void
    {
        $this->assertSame('', PersianDate::format(null));
    }
}
