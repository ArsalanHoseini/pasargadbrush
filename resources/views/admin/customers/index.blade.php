@extends('admin.layout')

@section('title', 'مشتریان')

@section('content')
<x-admin.page-header title="مشتریان" :description="$customers->total().' مشتری'">
    <x-slot:actions>
        <x-admin.button :href="route('admin.customers.export', request()->query())" variant="secondary" icon="download">خروجی Excel</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<x-admin.filter-bar :action="route('admin.customers.index')" placeholder="نام، شرکت، موبایل یا ایمیل…">
    <x-admin.filter-select name="has_orders" label="سفارش" :options="['yes' => 'دارای سفارش', 'no' => 'بدون سفارش']" />
    <x-admin.filter-date-range label="تاریخ عضویت" />
</x-admin.filter-bar>

@if ($customers->isEmpty())
    <x-admin.empty-state icon="users">{{ request()->hasAny(['q', 'has_orders', 'from', 'to']) ? 'مشتری‌ای با این فیلترها پیدا نشد.' : 'هنوز مشتری‌ای ثبت نشده است.' }}</x-admin.empty-state>
@else
    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">نام</th>
                    <th class="px-4 py-3 font-medium">تلفن</th>
                    <th class="px-4 py-3 font-medium">تعداد سفارش</th>
                    <th class="px-4 py-3 font-medium">مجموع خرید</th>
                    <th class="px-4 py-3 font-medium">عضویت</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($customers as $customer)
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium hover:text-brand-accent-dark">{{ $customer->name }}</a>
                            @if ($customer->company_name)
                                <span class="block text-xs text-neutral-500">{{ $customer->company_name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-500" dir="ltr">{{ $customer->phone }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $customer->orders_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-neutral-500" dir="ltr">{{ \App\Support\Money::toman((int) $customer->orders_total_rial) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-neutral-500">{{ persian_date($customer->created_at, \App\Support\PersianDate::COMPACT_DATE) }}</td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="text-neutral-600 hover:text-neutral-900">مشاهده</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $customers->links() }}</div>
@endif
@endsection
