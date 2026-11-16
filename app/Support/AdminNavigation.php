<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\ProductReview;
use App\Models\QuoteRequest;

/**
 * The admin sidebar: grouped sections, each item with its icon, the route
 * patterns that mark it active, and an optional "needs attention" count.
 */
class AdminNavigation
{
    /**
     * Items marked 'admin' are hidden from editors (their routes are admin-only too).
     *
     * @return list<array{label: string, items: list<array{label: string, route: string, active: string, icon: string, badge: int, admin?: bool}>}>
     */
    public static function groups(): array
    {
        $badges = self::badgeCounts();

        $groups = [
            ['label' => 'فروش', 'items' => [
                ['label' => 'سفارش‌ها', 'route' => 'admin.orders.index', 'active' => 'admin.orders.*', 'icon' => 'shopping-bag', 'badge' => $badges['orders'], 'admin' => true],
                ['label' => 'درخواست‌های استعلام', 'route' => 'admin.quote-requests.index', 'active' => 'admin.quote-requests.*', 'icon' => 'inbox', 'badge' => $badges['quote-requests'], 'admin' => true],
                ['label' => 'پیام‌ها', 'route' => 'admin.conversations.index', 'active' => 'admin.conversations.*', 'icon' => 'chat', 'badge' => $badges['conversations'], 'admin' => true],
                ['label' => 'مشتریان', 'route' => 'admin.customers.index', 'active' => 'admin.customers.*', 'icon' => 'users', 'badge' => 0, 'admin' => true],
                ['label' => 'گزارش فروش', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'icon' => 'chart', 'badge' => 0, 'admin' => true],
            ]],
            ['label' => 'کاتالوگ', 'items' => [
                ['label' => 'محصولات', 'route' => 'admin.products.index', 'active' => 'admin.products.*', 'icon' => 'cube', 'badge' => 0],
                ['label' => 'دسته‌بندی‌ها', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'icon' => 'folder', 'badge' => 0],
                ['label' => 'کاربردها', 'route' => 'admin.applications.index', 'active' => 'admin.applications.*', 'icon' => 'squares', 'badge' => 0],
                ['label' => 'نظرات محصولات', 'route' => 'admin.product-reviews.index', 'active' => 'admin.product-reviews.*', 'icon' => 'star', 'badge' => $badges['product-reviews']],
            ]],
            ['label' => 'محتوا', 'items' => [
                ['label' => 'مقالات', 'route' => 'admin.blog-posts.index', 'active' => 'admin.blog-posts.*', 'icon' => 'document-text', 'badge' => 0],
                ['label' => 'دسته‌بندی بلاگ', 'route' => 'admin.blog-categories.index', 'active' => 'admin.blog-categories.*', 'icon' => 'tag', 'badge' => 0],
                ['label' => 'دیدگاه‌ها', 'route' => 'admin.comments.index', 'active' => 'admin.comments.*', 'icon' => 'chat-bubble', 'badge' => $badges['comments']],
                ['label' => 'صفحات ثابت', 'route' => 'admin.pages.index', 'active' => 'admin.pages.*', 'icon' => 'document', 'badge' => 0],
                ['label' => 'منوهای سایت', 'route' => 'admin.menus.index', 'active' => 'admin.menus.*', 'icon' => 'menu', 'badge' => 0],
                ['label' => 'کتابخانه مدیا', 'route' => 'admin.media.index', 'active' => 'admin.media.*', 'icon' => 'photo', 'badge' => 0],
            ]],
            ['label' => 'تنظیمات', 'items' => [
                ['label' => 'تنظیمات سایت', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'icon' => 'cog', 'badge' => 0, 'admin' => true],
                ['label' => 'ارسال', 'route' => 'admin.shipping.edit', 'active' => 'admin.shipping.*', 'icon' => 'truck', 'badge' => 0, 'admin' => true],
                ['label' => 'ریدایرکت‌ها', 'route' => 'admin.redirects.index', 'active' => 'admin.redirects.*', 'icon' => 'arrows', 'badge' => 0, 'admin' => true],
                ['label' => 'کاربران پنل', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'user-group', 'badge' => 0, 'admin' => true],
                ['label' => 'گزارش فعالیت', 'route' => 'admin.activity.index', 'active' => 'admin.activity.*', 'icon' => 'clock', 'badge' => 0, 'admin' => true],
                ['label' => 'ایمپورت وردپرس', 'route' => 'admin.import.index', 'active' => 'admin.import.*', 'icon' => 'download', 'badge' => 0, 'admin' => true],
            ]],
        ];

        $isAdmin = (bool) auth()->user()?->isAdmin();

        return array_values(array_filter(array_map(
            fn (array $group) => [...$group, 'items' => array_values(array_filter(
                $group['items'],
                fn (array $item) => $isAdmin || ! ($item['admin'] ?? false),
            ))],
            $groups,
        ), fn (array $group) => $group['items'] !== []));
    }

    /**
     * The page title trail for the current route: the section and the item
     * that contains it, e.g. «کاتالوگ › محصولات».
     *
     * @return array{group: string, label: string, route: string}|null
     */
    public static function current(): ?array
    {
        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                if (request()->routeIs($item['active'])) {
                    return ['group' => $group['label'], 'label' => $item['label'], 'route' => $item['route']];
                }
            }
        }

        return null;
    }

    /**
     * Counts of items waiting for staff, computed once per request.
     *
     * @return array{orders: int, quote-requests: int, conversations: int, product-reviews: int, comments: int}
     */
    public static function badgeCounts(): array
    {
        return once(fn (): array => [
            'orders' => Order::where('status', 'pending')->count(),
            'quote-requests' => QuoteRequest::where('status', 'new')->count(),
            'conversations' => Conversation::where('status', Conversation::STATUS_OPEN)->count(),
            'product-reviews' => ProductReview::where('status', 'pending')->count(),
            'comments' => Comment::where('status', 'pending')->count(),
        ]);
    }
}
