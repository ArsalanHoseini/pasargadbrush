{{-- @param \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\Paginator $orders --}}
@if ($orders->isEmpty())
    <p class="text-sm text-neutral-500">
        هنوز سفارشی ندارید. <a href="{{ route('home') }}" class="text-brand-accent-dark hover:underline">مشاهده محصولات</a>
    </p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-right text-xs text-neutral-500">
                <tr>
                    <th class="py-2 font-medium">شماره سفارش</th>
                    <th class="py-2 font-medium">تاریخ</th>
                    <th class="py-2 font-medium">مبلغ</th>
                    <th class="py-2 font-medium">وضعیت</th>
                    <th class="py-2"><span class="sr-only">جزئیات</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($orders as $order)
                    <tr>
                        <td class="py-3" dir="ltr">{{ $order->order_number }}</td>
                        <td class="py-3">{{ persian_date($order->placed_at, \App\Support\PersianDate::DATE) }}</td>
                        <td class="py-3" dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</td>
                        <td class="py-3">
                            {{ \App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status }}
                            @if ($order->requires_price_confirmation)
                                <span class="mr-1 rounded bg-amber-50 px-1.5 py-0.5 text-xs text-amber-800">در انتظار تأیید قیمت</span>
                            @endif
                        </td>
                        <td class="py-3 text-left">
                            <a href="{{ route('account.orders.show', $order) }}" class="text-brand-accent-dark hover:underline">جزئیات</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
