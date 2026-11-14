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

    public function test_parses_a_persian_date_as_the_start_of_that_day_in_tehran(): void
    {
        $this->assertSame('2026-10-31 00:00:00 +03:30', PersianDate::parse('۱۴۰۵/۰۸/۰۹')?->format('Y-m-d H:i:s P'));
        $this->assertSame('2026-03-21', PersianDate::parse('1405-1-1')?->format('Y-m-d'));
    }

    public function test_parsing_rejects_impossible_or_malformed_dates(): void
    {
        $this->assertNull(PersianDate::parse('1405/07/31'));
        $this->assertNull(PersianDate::parse('1405/13/01'));
        $this->assertNull(PersianDate::parse('1404/12/30'));
        $this->assertNull(PersianDate::parse('دیروز'));
        $this->assertNull(PersianDate::parse(null));
    }

    public function test_start_of_persian_month_and_year(): void
    {
        $date = new DateTimeImmutable('2026-11-14 15:00:00', new DateTimeZone('Asia/Tehran'));

        $this->assertSame('2026-10-23 00:00:00', PersianDate::startOfMonth($date)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-23', PersianDate::startOfMonth($date, 1)->format('Y-m-d'));
        $this->assertSame('2026-03-21', PersianDate::startOfYear($date)->format('Y-m-d'));
    }
}
