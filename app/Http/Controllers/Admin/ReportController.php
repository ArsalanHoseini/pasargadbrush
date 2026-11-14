<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SalesReport;
use App\Support\CsvExport;
use App\Support\PersianDate;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales report for a Shamsi date range (admins only).
 */
class ReportController extends Controller
{
    public const PRESETS = [
        '30-days' => '۳۰ روز اخیر',
        'this-month' => 'این ماه',
        'last-month' => 'ماه قبل',
        '3-months' => '۳ ماه اخیر',
        'this-year' => 'امسال',
    ];

    public function index(Request $request, SalesReport $report): View
    {
        [$from, $toExclusive, $preset] = $this->range($request);

        return view('admin.reports.index', [
            'report' => $report->build($from, $toExclusive),
            'from' => $from,
            'to' => $toExclusive->modify('-1 day'),
            'preset' => $preset,
        ]);
    }

    public function export(Request $request, SalesReport $report): StreamedResponse
    {
        [$from, $toExclusive] = $this->range($request);

        return CsvExport::download('sales-by-product', ['محصول', 'تعداد فروش', 'مبلغ (تومان)'], array_map(
            fn (array $row) => [$row['name'], $row['quantity'], intdiv($row['revenue_rial'], 10)],
            $report->build($from, $toExclusive)['products'],
        ));
    }

    /**
     * Typed from/to dates win; otherwise a preset (default: last 30 days).
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable, 2: string|null}
     */
    private function range(Request $request): array
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Tehran'));
        $tomorrow = $today->modify('+1 day');
        $from = PersianDate::parse($request->query('from'));
        $to = PersianDate::parse($request->query('to'));

        if ($from || $to) {
            $from ??= PersianDate::startOfMonth($to ?? $today);
            $toExclusive = $to ? $to->modify('+1 day') : $tomorrow;

            return $from < $toExclusive ? [$from, $toExclusive, null] : [$toExclusive->modify('-1 day'), $from->modify('+1 day'), null];
        }

        $preset = array_key_exists((string) $request->query('preset'), self::PRESETS) ? $request->query('preset') : '30-days';

        return match ($preset) {
            'this-month' => [PersianDate::startOfMonth($today), $tomorrow, $preset],
            'last-month' => [PersianDate::startOfMonth($today, 1), PersianDate::startOfMonth($today), $preset],
            '3-months' => [PersianDate::startOfMonth($today, 2), $tomorrow, $preset],
            'this-year' => [PersianDate::startOfYear($today), $tomorrow, $preset],
            default => [$today->modify('-29 days'), $tomorrow, '30-days'],
        };
    }
}
