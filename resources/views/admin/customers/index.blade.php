@extends('admin.layout')

@section('title', 'مشتریان')

@section('content')
<h1 class="mb-6 text-lg font-semibold">مشتریان</h1>

@if ($customers->isEmpty())
    <p class="text-sm text-neutral-500">هنوز مشتری‌ای ثبت نشده است.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">نام</th>
                    <th class="px-4 py-3 font-medium">تلفن</th>
                    <th class="px-4 py-3 font-medium">تعداد سفارش</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3">{{ $customer->name }}</td>
                        <td class="px-4 py-3 text-neutral-500" dir="ltr">{{ $customer->phone }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $customer->orders_count }}</td>
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
