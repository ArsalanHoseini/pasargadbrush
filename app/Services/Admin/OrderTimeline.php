<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * The order's history for staff, newest first: when it was placed, every
 * change saved in audit_logs (who, when, what) and internal notes.
 */
class OrderTimeline
{
    /**
     * @return Collection<int, array{type: 'created'|'change'|'note', at: \DateTimeInterface, user: ?string, title: string, details: list<string>}>
     */
    public function for(Order $order): Collection
    {
        $logs = AuditLog::query()
            ->with('user')
            ->where('auditable_type', Order::class)
            ->where('auditable_id', $order->id)
            ->latest()
            ->latest('id')
            ->get();

        $entries = $logs->reject(fn (AuditLog $log) => $log->action === 'order.created_manually')
            ->map(fn (AuditLog $log) => $log->action === 'order.note'
                ? ['type' => 'note', 'at' => $log->created_at, 'user' => $log->user?->name, 'title' => 'یادداشت داخلی', 'details' => [(string) ($log->changes['body'] ?? '')]]
                : ['type' => 'change', 'at' => $log->created_at, 'user' => $log->user?->name, 'title' => ($log->changes['bulk'] ?? false) ? 'تغییر گروهی' : 'به‌روزرسانی سفارش', 'details' => $this->describe($log->changes ?? [])])
            ->values();

        $creation = $logs->firstWhere('action', 'order.created_manually');
        $quoteRequestId = $creation?->changes['quote_request_id'] ?? null;

        return $entries->push([
            'type' => 'created',
            'at' => $order->placed_at ?? $order->created_at,
            'user' => $creation?->user?->name ?? $order->createdBy?->name,
            'title' => $order->source === Order::SOURCE_MANUAL ? 'سفارش به‌صورت دستی ثبت شد' : 'سفارش توسط مشتری در سایت ثبت شد',
            'details' => array_values(array_filter([
                $quoteRequestId ? 'از درخواست استعلام #'.$quoteRequestId : null,
                'مبلغ: '.Money::toman((int) ($creation?->changes['total_rial'] ?? $order->total_rial)),
            ])),
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return list<string>
     */
    private function describe(array $changes): array
    {
        $lines = [];

        foreach ($changes as $field => $value) {
            $line = match ($field) {
                'status' => 'وضعیت: '.(Order::STATUS_LABELS[$value] ?? $value),
                'payment_status' => 'پرداخت: '.(Order::PAYMENT_STATUS_LABELS[$value] ?? $value),
                'shipment_tracking_code' => filled($value) ? 'کد رهگیری: '.$value : 'کد رهگیری حذف شد',
                'notes' => 'توضیحات سفارش ویرایش شد',
                'shipping_cost_rial' => 'هزینه ارسال: '.Money::toman((int) $value),
                'total_rial' => 'مبلغ کل: '.Money::toman((int) $value),
                'requires_price_confirmation' => $value ? null : 'قیمت نهایی تأیید شد',
                'item_prices_rial' => 'قیمت واحد اقلام تغییر کرد',
                'customer_notified' => 'به مشتری پیامک/ایمیل ارسال شد',
                default => null,
            };

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
