<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Support\PersianDate;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Sales figures for a date range. Like the dashboard, revenue counts every
 * order that isn't cancelled (paid or not), by placed_at.
 */
class SalesReport
{
    /**
     * @param  DateTimeImmutable  $toExclusive  Midnight after the last day.
     * @return array{
     *     summary: array{revenue_rial: int, order_count: int, average_rial: int, items_sold: int, paid_revenue_rial: int, cancelled_count: int},
     *     months: list<array{label: string, revenue_rial: int, order_count: int}>,
     *     products: list<array{product_id: int|null, name: string, quantity: int, revenue_rial: int}>,
     *     statuses: list<array{label: string, order_count: int, revenue_rial: int}>,
     *     sources: list<array{label: string, order_count: int, revenue_rial: int}>,
     *     provinces: list<array{label: string, order_count: int, revenue_rial: int}>
     * }
     */
    public function build(DateTimeImmutable $from, DateTimeImmutable $toExclusive): array
    {
        /** @var Collection<int, Order> $orders */
        $orders = Order::query()
            ->with(['items', 'shippingAddress'])
            ->where('placed_at', '>=', $from)
            ->where('placed_at', '<', $toExclusive)
            ->orderBy('placed_at')
            ->get();

        $counted = $orders->where('status', '!=', 'cancelled');
        $revenue = (int) $counted->sum('total_rial');

        return [
            'summary' => [
                'revenue_rial' => $revenue,
                'order_count' => $counted->count(),
                'average_rial' => $counted->isEmpty() ? 0 : intdiv($revenue, $counted->count()),
                'items_sold' => (int) $counted->sum(fn (Order $order) => $order->items->sum('quantity')),
                'paid_revenue_rial' => (int) $counted->where('payment_status', 'paid')->sum('total_rial'),
                'cancelled_count' => $orders->count() - $counted->count(),
            ],
            'months' => $counted
                ->groupBy(fn (Order $order) => PersianDate::format($order->placed_at, 'yyyy/MM'))
                ->map(fn ($group) => [
                    'label' => PersianDate::format($group->first()->placed_at, 'MMMM y'),
                    'revenue_rial' => (int) $group->sum('total_rial'),
                    'order_count' => $group->count(),
                ])
                ->values()
                ->all(),
            'products' => $counted
                ->flatMap(fn (Order $order) => $order->items)
                ->groupBy(fn ($item) => $item->product_id ?? 'name:'.$item->name_snapshot)
                ->map(fn ($items) => [
                    'product_id' => $items->first()->product_id,
                    'name' => $items->first()->name_snapshot,
                    'quantity' => (int) $items->sum('quantity'),
                    'revenue_rial' => (int) $items->sum('line_total_rial'),
                ])
                ->sortByDesc('revenue_rial')
                ->values()
                ->all(),
            'statuses' => $this->breakdown($orders, fn (Order $order) => Order::STATUS_LABELS[$order->status] ?? $order->status),
            'sources' => $this->breakdown($counted, fn (Order $order) => $order->source === Order::SOURCE_MANUAL ? 'ثبت دستی' : 'سایت'),
            'provinces' => $this->breakdown($counted, fn (Order $order) => $order->shippingAddress?->province ?: 'نامشخص'),
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<array{label: string, order_count: int, revenue_rial: int}>
     */
    private function breakdown(Collection $orders, callable $key): array
    {
        return $orders->groupBy($key)
            ->map(fn ($group, $label) => ['label' => (string) $label, 'order_count' => $group->count(), 'revenue_rial' => (int) $group->sum('total_rial')])
            ->sortByDesc('revenue_rial')
            ->values()
            ->all();
    }
}
