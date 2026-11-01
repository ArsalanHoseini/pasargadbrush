@extends('layouts.public')

@section('title', 'سبد خرید')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <h1 class="mb-6 text-2xl font-bold text-neutral-900">سبد خرید</h1>

    @if (session('status'))
        <div class="mb-6 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if (session('reorder_skipped'))
        <div class="mb-6 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            <p class="mb-1 font-medium">این اقلام به سبد اضافه نشدند:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach (session('reorder_skipped') as $skipped)
                    <li>{{ $skipped['name'] }} — {{ $skipped['reason'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if (! $cart || $cart->items->isEmpty())
        <p class="text-sm text-neutral-500">
            سبد خرید شما خالی است. <a href="{{ route('home') }}" class="text-brand-accent-dark hover:underline">مشاهده محصولات</a>
        </p>
    @else
        <div class="space-y-3">
            @foreach ($cart->items as $item)
                <div @class([
                    'flex items-center justify-between gap-4 rounded-lg border bg-white p-4',
                    'border-red-300' => isset($problems[$item->id]),
                    'border-neutral-200' => ! isset($problems[$item->id]),
                ])>
                    <div>
                        <p class="font-medium text-neutral-900">{{ $item->variant->product->name }}</p>
                        <p class="text-xs text-neutral-500">{{ $item->variant->label }}</p>
                        @include('orders.partials.customization', ['customization' => $item->customization])
                        <p class="mt-1 text-sm" dir="ltr">{{ \App\Support\Money::toman($item->variant->price_rial) }}</p>
                        @isset($problems[$item->id])
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $problems[$item->id] }}</p>
                        @endisset
                    </div>

                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('cart.items.update', $item) }}">
                            @csrf @method('PATCH')
                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="0" max="1000"
                                   onchange="this.form.submit()"
                                   class="w-16 rounded border border-neutral-300 px-2 py-1 text-sm" dir="ltr">
                        </form>
                        <p class="w-28 text-left text-sm font-medium" dir="ltr">
                            {{ \App\Support\Money::toman($item->variant->price_rial * $item->quantity) }}
                        </p>
                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}"
                              onsubmit="return confirm('حذف شود؟');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">حذف</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-lg border border-neutral-200 bg-neutral-50 p-4">
            @include('orders.partials.totals', ['subtotalRial' => $total, 'shipping' => $shipping, 'priceNeedsConfirmation' => $priceNeedsConfirmation])
        </div>

        @if ($problems === [])
            <a href="{{ route('checkout.create') }}"
               class="mt-6 block w-full rounded-full bg-brand-accent px-6 py-3 text-center text-sm font-semibold text-white hover:bg-brand-accent-dark">
                ادامه و تکمیل سفارش
            </a>
        @else
            <p class="mt-6 rounded-full bg-neutral-200 px-6 py-3 text-center text-sm font-semibold text-neutral-500">
                برای ادامه، ابتدا موارد مشخص‌شده با رنگ قرمز را اصلاح یا حذف کنید.
            </p>
        @endif
    @endif
</div>
@endsection
