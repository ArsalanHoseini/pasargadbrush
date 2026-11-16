@extends('admin.layout')

@section('title', 'پیام‌های مشتریان')

@section('content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">پیام‌های مشتریان</h1>

<div class="mb-6 flex flex-wrap gap-2 text-sm">
    <a href="{{ route('admin.conversations.index') }}"
       class="rounded px-3 py-1.5 {{ ! $status ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">همه</a>
    @foreach (\App\Models\Conversation::STAFF_STATUS_LABELS as $value => $label)
        <a href="{{ route('admin.conversations.index', ['status' => $value]) }}"
           class="rounded px-3 py-1.5 {{ $status === $value ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">{{ $label }}</a>
    @endforeach
</div>

@if ($conversations->isEmpty())
    <p class="text-sm text-neutral-500">پیامی وجود ندارد.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">موضوع</th>
                    <th class="px-4 py-3 font-medium">مشتری</th>
                    <th class="px-4 py-3 font-medium">مربوط به</th>
                    <th class="px-4 py-3 font-medium">آخرین پیام</th>
                    <th class="px-4 py-3 font-medium">وضعیت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($conversations as $conversation)
                    <tr class="{{ $conversation->staff_unread ? 'bg-amber-50/50 font-medium' : '' }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.conversations.show', $conversation) }}" class="hover:underline">{{ $conversation->subject }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $conversation->customer->name ?: '—' }} <span class="text-xs text-neutral-500" dir="ltr">{{ $conversation->customer->phone }}</span></td>
                        <td class="px-4 py-3 text-xs">
                            @if ($conversation->order)
                                سفارش <span dir="ltr">{{ $conversation->order->order_number }}</span>
                            @elseif ($conversation->quoteRequest)
                                استعلام #{{ $conversation->quoteRequest->id }}
                            @else
                                تیکت عمومی
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-500">{{ persian_date($conversation->last_message_at, \App\Support\PersianDate::COMPACT) }}</td>
                        <td class="px-4 py-3">{{ \App\Models\Conversation::STAFF_STATUS_LABELS[$conversation->status] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-admin.pagination :paginator="$conversations" />
@endif
@endsection
