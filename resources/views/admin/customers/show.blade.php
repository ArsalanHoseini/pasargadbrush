@extends('admin.layout')

@section('title', $customer->name)

@section('content')
<h1 class="mb-6 text-lg font-semibold">{{ $customer->name }}</h1>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">اطلاعات تماس</h2>
        <dl class="space-y-2 text-sm">
            <div><dt class="text-neutral-500">تلفن</dt><dd class="font-medium" dir="ltr">{{ $customer->phone }}</dd></div>
            @if ($customer->email)
                <div><dt class="text-neutral-500">ایمیل</dt><dd class="font-medium" dir="ltr">{{ $customer->email }}</dd></div>
            @endif
        </dl>

        @if ($addresses->isNotEmpty())
            <h3 class="mb-2 mt-6 text-sm font-medium text-neutral-500">آدرس‌ها</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($addresses as $address)
                    <li class="rounded border border-neutral-100 p-3">
                        {{ $address->province }}، {{ $address->city }} — {{ $address->address_line }}
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">سفارش‌ها</h2>
        @if ($orders->isEmpty())
            <p class="text-sm text-neutral-500">سفارشی ثبت نشده است.</p>
        @else
            <ul class="divide-y divide-neutral-100 text-sm">
                @foreach ($orders as $order)
                    <li class="flex items-center justify-between py-2">
                        <a href="{{ route('admin.orders.edit', $order) }}" class="text-brand-accent-dark hover:underline" dir="ltr">
                            {{ $order->order_number }}
                        </a>
                        <span dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
