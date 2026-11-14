<?php

namespace App\Services\Admin;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Numbers for the admin dashboard. Sales figures count every order that
 * wasn't cancelled, by the day it was placed (app time zone: Tehran).
 */
class DashboardMetrics
{
    /** A tracked variant at or below this stock is flagged on the dashboard. */
    public const LOW_STOCK_THRESHOLD = 5;

    public const CHART_DAYS = 30;

    /**
     * Sales for today, the last 7 days and the last 30 days, each with the
     * equal-length period right before it for comparison.
     *
     * @return list<array{label: string, revenue_rial: int, order_count: int, previous_revenue_rial: int, change_percent: int|null}>
     */
    public function salesPeriods(): array
    {
        $periods = [
            ['label' => 'امروز', 'days' => 1],
            ['label' => '۷ روز اخیر', 'days' => 7],
            ['label' => '۳۰ روز اخیر', 'days' => 30],
        ];

        return array_map(function (array $period): array {
            $start = today()->subDays($period['days'] - 1);
            $previousStart = $start->copy()->subDays($period['days']);

            $current = $this->salesBetween($start);
            $previous = $this->salesBetween($previousStart, $start);

            return [
                'label' => $period['label'],
                'revenue_rial' => $current['revenue_rial'],
                'order_count' => $current['order_count'],
                'previous_revenue_rial' => $previous['revenue_rial'],
                'change_percent' => $previous['revenue_rial'] > 0
                    ? (int) round(($current['revenue_rial'] - $previous['revenue_rial']) / $previous['revenue_rial'] * 100)
                    : null,
            ];
        }, $periods);
    }

    /**
     * One entry per day for the last CHART_DAYS days, oldest first.
     *
     * @return list<array{date: Carbon, revenue_rial: int, order_count: int}>
     */
    public function dailySales(): array
    {
        $start = today()->subDays(self::CHART_DAYS - 1);

        $ordersByDay = $this->countedOrders()
            ->where('placed_at', '>=', $start)
            ->get(['placed_at', 'total_rial'])
            ->groupBy(fn (Order $order): string => $order->placed_at->toDateString());

        $days = [];

        for ($offset = 0; $offset < self::CHART_DAYS; $offset++) {
            $day = $start->copy()->addDays($offset);
            $orders = $ordersByDay->get($day->toDateString(), collect());

            $days[] = [
                'date' => $day,
                'revenue_rial' => (int) $orders->sum('total_rial'),
                'order_count' => $orders->count(),
            ];
        }

        return $days;
    }

    /**
     * What staff should deal with next, most urgent first; only non-empty
     * entries are returned. Without $includeSales (editors) the order,
     * message and quote entries are left out.
     *
     * @return list<array{label: string, count: int, url: string, icon: string, tone: string, admin?: bool}>
     */
    public function actionItems(bool $includeSales = true): array
    {
        $items = [
            [
                'label' => 'سفارش منتظر تأیید قیمت نهایی',
                'admin' => true,
                'count' => Order::where('requires_price_confirmation', true)->whereNotIn('status', ['cancelled', 'completed'])->count(),
                'url' => route('admin.orders.index', ['price_confirmation' => 1]),
                'icon' => 'banknotes',
                'tone' => 'red',
            ],
            [
                'label' => 'سفارش جدید در انتظار بررسی',
                'admin' => true,
                'count' => Order::where('status', 'pending')->count(),
                'url' => route('admin.orders.index', ['status' => 'pending']),
                'icon' => 'shopping-bag',
                'tone' => 'amber',
            ],
            [
                'label' => 'پیام بی‌پاسخ مشتری',
                'admin' => true,
                'count' => Conversation::where('status', Conversation::STATUS_OPEN)->count(),
                'url' => route('admin.conversations.index', ['status' => Conversation::STATUS_OPEN]),
                'icon' => 'chat',
                'tone' => 'amber',
            ],
            [
                'label' => 'درخواست استعلام جدید',
                'admin' => true,
                'count' => QuoteRequest::where('status', 'new')->count(),
                'url' => route('admin.quote-requests.index', ['status' => 'new']),
                'icon' => 'inbox',
                'tone' => 'amber',
            ],
            [
                'label' => 'تنوع محصول با موجودی کم',
                'count' => $this->lowStockVariants()->count(),
                'url' => route('admin.products.index', ['stock' => 'low']),
                'icon' => 'cube',
                'tone' => 'red',
            ],
            [
                'label' => 'نظر محصول در انتظار تأیید',
                'count' => ProductReview::where('status', 'pending')->count(),
                'url' => route('admin.product-reviews.index', ['status' => 'pending']),
                'icon' => 'star',
                'tone' => 'neutral',
            ],
            [
                'label' => 'دیدگاه بلاگ در انتظار تأیید',
                'count' => Comment::where('status', 'pending')->count(),
                'url' => route('admin.comments.index', ['status' => 'pending']),
                'icon' => 'chat-bubble',
                'tone' => 'neutral',
            ],
        ];

        return array_values(array_filter($items, fn (array $item): bool => $item['count'] > 0 && ($includeSales || ! ($item['admin'] ?? false))));
    }

    /**
     * Best sellers of the last 30 days by revenue.
     *
     * @return Collection<int, object{name: string, product_id: int|null, quantity: int, revenue_rial: int}>
     */
    public function topProducts(int $limit = 5): Collection
    {
        return OrderItem::query()
            ->whereHas('order', fn (Builder $query) => $query
                ->where('status', '!=', 'cancelled')
                ->where('placed_at', '>=', today()->subDays(self::CHART_DAYS - 1)))
            ->selectRaw('product_id, max(name_snapshot) as name, sum(quantity) as quantity, sum(line_total_rial) as revenue_rial')
            ->groupBy('product_id')
            ->orderByDesc('revenue_rial')
            ->orderByDesc('quantity')
            ->limit($limit)
            ->toBase()
            ->get();
    }

    /**
     * @return Builder<ProductVariant>
     */
    public function lowStockVariants(): Builder
    {
        return ProductVariant::query()
            ->whereNotNull('stock_quantity')
            ->where('stock_quantity', '<=', self::LOW_STOCK_THRESHOLD);
    }

    /**
     * @return array{customers: int, new_customers_30_days: int}
     */
    public function customerCounts(): array
    {
        return [
            'customers' => Customer::count(),
            'new_customers_30_days' => Customer::where('created_at', '>=', today()->subDays(self::CHART_DAYS - 1))->count(),
        ];
    }

    /**
     * @return array{revenue_rial: int, order_count: int}
     */
    private function salesBetween(Carbon $start, ?Carbon $end = null): array
    {
        $totals = $this->countedOrders()
            ->where('placed_at', '>=', $start)
            ->when($end, fn (Builder $query) => $query->where('placed_at', '<', $end))
            ->selectRaw('count(*) as order_count, coalesce(sum(total_rial), 0) as revenue_rial')
            ->toBase()
            ->first();

        return [
            'revenue_rial' => (int) $totals->revenue_rial,
            'order_count' => (int) $totals->order_count,
        ];
    }

    /**
     * @return Builder<Order>
     */
    private function countedOrders(): Builder
    {
        return Order::query()->where('status', '!=', 'cancelled');
    }
}
