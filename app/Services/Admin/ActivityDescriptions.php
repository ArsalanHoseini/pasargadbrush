<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

/**
 * Persian labels for audit-log actions and a link to what each one touched.
 */
class ActivityDescriptions
{
    /** Filter groups on the activity page: key => [label, action prefixes]. */
    public const GROUPS = [
        'orders' => ['سفارش‌ها', ['order.']],
        'catalog' => ['محصولات و نظرات', ['product.', 'review.']],
        'content' => ['محتوا', ['comment.']],
        'sales' => ['استعلام‌ها', ['quote_request.']],
        'settings' => ['تنظیمات', ['settings.', 'shipping.']],
        'staff' => ['کاربران و ورود', ['user.', 'profile.', 'auth.']],
    ];

    private const LABELS = [
        'order.created_manually' => 'ثبت سفارش دستی',
        'order.updated' => 'ویرایش سفارش',
        'order.note' => 'یادداشت روی سفارش',
        'product.created' => 'ایجاد محصول',
        'product.updated' => 'ویرایش محصول',
        'product.deleted' => 'حذف محصول',
        'product.bulk' => 'عملیات گروهی روی محصولات',
        'review.bulk' => 'عملیات گروهی روی نظرات محصول',
        'comment.bulk' => 'عملیات گروهی روی دیدگاه‌ها',
        'quote_request.bulk' => 'عملیات گروهی روی استعلام‌ها',
        'settings.updated' => 'ویرایش تنظیمات سایت',
        'shipping.updated' => 'ویرایش تنظیمات ارسال',
        'user.created' => 'ساخت حساب کاربری',
        'user.updated' => 'ویرایش حساب کاربری',
        'user.deleted' => 'حذف حساب کاربری',
        'profile.updated' => 'ویرایش مشخصات خود',
        'profile.password_changed' => 'تغییر رمز عبور خود',
        'auth.login' => 'ورود به پنل',
    ];

    private const BULK_ACTION_LABELS = [
        'activate' => 'فعال‌سازی', 'deactivate' => 'غیرفعال‌سازی', 'delete' => 'حذف',
        'approve' => 'تأیید', 'spam' => 'اسپم',
    ];

    public function label(AuditLog $log): string
    {
        if ($log->action === 'order.updated' && ($log->changes['bulk'] ?? false)) {
            return 'تغییر گروهی سفارش';
        }

        return self::LABELS[$log->action] ?? $log->action;
    }

    /**
     * A short Persian summary of the recorded details, or null.
     */
    public function summary(AuditLog $log): ?string
    {
        $changes = $log->changes ?? [];

        return match (true) {
            str_ends_with($log->action, '.bulk') => (self::BULK_ACTION_LABELS[$changes['action'] ?? ''] ?? (string) ($changes['action'] ?? ''))
                .' — '.count($changes['ids'] ?? []).' مورد',
            $log->action === 'order.note' => mb_strimwidth((string) ($changes['body'] ?? ''), 0, 120, '…'),
            $log->action === 'order.updated' => isset($changes['status']) ? 'وضعیت: '.(Order::STATUS_LABELS[$changes['status']] ?? $changes['status']) : null,
            str_starts_with($log->action, 'user.') => trim(($changes['name'] ?? '').(isset($changes['role']) ? ' — نقش: '.(User::ROLE_LABELS[$changes['role']] ?? $changes['role']) : '').(isset($changes['password_changed']) ? ' — رمز عبور تغییر کرد' : '')) ?: null,
            str_starts_with($log->action, 'product.') => $changes['name'] ?? null,
            default => null,
        };
    }

    /**
     * @return array{label: string, url: string|null}|null
     */
    public function subject(AuditLog $log): ?array
    {
        $subject = $log->auditable;

        return match (true) {
            $subject instanceof Order => ['label' => $subject->order_number, 'url' => route('admin.orders.edit', $subject)],
            $subject instanceof Product => ['label' => $subject->name, 'url' => route('admin.products.edit', $subject)],
            $subject instanceof User => ['label' => $subject->name, 'url' => route('admin.users.edit', $subject)],
            default => null,
        };
    }
}
