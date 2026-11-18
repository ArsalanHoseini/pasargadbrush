@extends('account.layout')

@section('title', 'پیام‌ها')

@section('account-content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-xl font-bold text-neutral-900">پیام‌ها</h1>
    <a href="{{ route('account.conversations.create') }}" class="rounded-full bg-brand-accent-dark px-5 py-2 text-sm font-semibold text-white hover:bg-brand-accent-darker">+ پیام جدید به کارشناس</a>
</div>

@forelse ($conversations as $conversation)
    <a href="{{ route('account.conversations.show', $conversation) }}"
       class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-white p-4 text-sm hover:border-neutral-400 {{ $conversation->customer_unread ? 'border-brand-accent' : 'border-neutral-200' }}">
        <div class="min-w-0">
            <p class="truncate font-medium text-neutral-900">
                @if ($conversation->customer_unread)
                    <span class="me-1 inline-block h-2 w-2 rounded-full bg-brand-accent" aria-label="خوانده‌نشده"></span>
                @endif
                {{ $conversation->subject }}
            </p>
            <p class="text-xs text-neutral-500">{{ persian_date($conversation->last_message_at) }}</p>
        </div>
        <span class="rounded bg-neutral-100 px-2 py-0.5 text-xs">{{ \App\Models\Conversation::CUSTOMER_STATUS_LABELS[$conversation->status] }}</span>
    </a>
@empty
    <div class="rounded-lg border border-neutral-200 bg-white p-5 text-sm text-neutral-500">
        هنوز پیامی ندارید. برای هر سؤال درباره‌ی سفارش‌ها، استعلام‌ها یا محصولات، به کارشناسان ما پیام بدهید.
    </div>
@endforelse

<div class="mt-6">{{ $conversations->links() }}</div>
@endsection
