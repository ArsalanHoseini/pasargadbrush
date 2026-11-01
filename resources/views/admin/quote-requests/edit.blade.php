@extends('admin.layout')

@section('title', 'درخواست استعلام قیمت')

@section('content')
<h1 class="mb-6 text-lg font-semibold">درخواست استعلام قیمت</h1>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">اطلاعات درخواست</h2>
        <dl class="space-y-3 text-sm">
            <div><dt class="text-neutral-500">نام</dt><dd class="font-medium">{{ $quoteRequest->name }}</dd></div>
            <div><dt class="text-neutral-500">تلفن</dt><dd class="font-medium" dir="ltr">{{ $quoteRequest->phone }}</dd></div>
            @if ($quoteRequest->email)
                <div><dt class="text-neutral-500">ایمیل</dt><dd class="font-medium" dir="ltr">{{ $quoteRequest->email }}</dd></div>
            @endif
            @if ($quoteRequest->company)
                <div><dt class="text-neutral-500">شرکت</dt><dd class="font-medium">{{ $quoteRequest->company }}</dd></div>
            @endif
            <div>
                <dt class="text-neutral-500">محصول</dt>
                <dd class="font-medium">
                    @if ($quoteRequest->product)
                        <a href="{{ route('products.show', $quoteRequest->product) }}" target="_blank" class="text-brand-accent-dark hover:underline">
                            {{ $quoteRequest->product->name }}
                        </a>
                    @else
                        درخواست عمومی
                    @endif
                </dd>
            </div>
            @if ($quoteRequest->requested_quantity)
                <div><dt class="text-neutral-500">تیراژ/تعداد</dt><dd class="font-medium">{{ $quoteRequest->requested_quantity }}</dd></div>
            @endif
            @if (! empty($quoteRequest->customization))
                <div>
                    <dt class="text-neutral-500">ابعاد درخواستی</dt>
                    <dd>@include('orders.partials.customization', ['customization' => $quoteRequest->customization])</dd>
                </div>
            @endif
            <div><dt class="text-neutral-500">پیام</dt><dd class="whitespace-pre-line">{{ $quoteRequest->message }}</dd></div>
            <div><dt class="text-neutral-500">تاریخ ثبت</dt><dd>{{ $quoteRequest->created_at->format('Y-m-d H:i') }}</dd></div>
        </dl>
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <div class="mb-6 rounded border border-neutral-200 bg-neutral-50 p-4">
            @if ($quoteRequest->order)
                <p class="text-sm">
                    برای این درخواست سفارش ثبت شده است:
                    <a href="{{ route('admin.orders.edit', $quoteRequest->order) }}" class="font-medium text-blue-700 hover:underline" dir="ltr">{{ $quoteRequest->order->order_number }}</a>
                </p>
            @else
                <p class="mb-3 text-sm text-neutral-600">مشتری قیمت را پذیرفت؟ سفارش را با اطلاعات و ابعاد همین درخواست ثبت کنید.</p>
                <a href="{{ route('admin.orders.create', ['quote_request' => $quoteRequest->id]) }}"
                   class="inline-block rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
                    ثبت سفارش از این درخواست
                </a>
            @endif
        </div>

        <h2 class="mb-4 text-sm font-medium text-neutral-500">مدیریت درخواست</h2>
        <form method="POST" action="{{ route('admin.quote-requests.update', $quoteRequest) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium">وضعیت</label>
                <select name="status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    <option value="new" @selected($quoteRequest->status === 'new')>جدید</option>
                    <option value="contacted" @selected($quoteRequest->status === 'contacted')>تماس گرفته شد</option>
                    <option value="quoted" @selected($quoteRequest->status === 'quoted')>قیمت ارسال شد</option>
                    <option value="closed" @selected($quoteRequest->status === 'closed')>بسته شده</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">یادداشت داخلی</label>
                <textarea name="admin_notes" rows="5" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('admin_notes', $quoteRequest->admin_notes) }}</textarea>
            </div>

            <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
                ذخیره
            </button>
        </form>

        <form method="POST" action="{{ route('admin.quote-requests.destroy', $quoteRequest) }}" class="mt-4"
              onsubmit="return confirm('این درخواست حذف شود؟');">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:text-red-800">حذف درخواست</button>
        </form>
    </div>
</div>
@endsection
