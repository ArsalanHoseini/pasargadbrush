@extends('admin.layout')

@section('title', 'گزارش فعالیت')

@section('content')
<x-admin.page-header title="گزارش فعالیت" description="چه کسی، چه زمانی، چه کاری در پنل انجام داده است." />

<form method="GET" action="{{ route('admin.activity.index') }}"
      class="mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-neutral-200 bg-white p-3">
    <x-admin.filter-select name="user" label="کاربر" :options="$userOptions" />
    <x-admin.filter-select name="group" label="بخش" :options="$groupOptions" />
    <x-admin.filter-date-range />
    <x-admin.button variant="secondary" icon="funnel">اعمال</x-admin.button>
    @if (request()->hasAny(['user', 'group', 'from', 'to']))
        <a href="{{ route('admin.activity.index') }}" class="px-2 text-sm text-neutral-500 hover:text-neutral-900">پاک کردن فیلترها</a>
    @endif
</form>

@if ($logs->isEmpty())
    <x-admin.empty-state icon="clock">فعالیتی با این فیلترها ثبت نشده است.</x-admin.empty-state>
@else
    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right text-xs text-neutral-500">
                <tr>
                    <th class="px-4 py-3 font-medium">زمان</th>
                    <th class="px-4 py-3 font-medium">کاربر</th>
                    <th class="px-4 py-3 font-medium">کار</th>
                    <th class="px-4 py-3 font-medium">مورد</th>
                    <th class="px-4 py-3 font-medium">جزئیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($logs as $log)
                    @php $subject = $descriptions->subject($log); @endphp
                    <tr class="align-top hover:bg-neutral-50">
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-neutral-500">{{ persian_date($log->created_at, \App\Support\PersianDate::COMPACT) }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $log->user?->name ?? 'سیستم' }}</td>
                        <td class="px-4 py-3 font-medium">{{ $descriptions->label($log) }}</td>
                        <td class="px-4 py-3">
                            @if ($subject)
                                <a href="{{ $subject['url'] }}" class="text-brand-accent-dark hover:underline">{{ $subject['label'] }}</a>
                            @else
                                <span class="text-neutral-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ $descriptions->summary($log) ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-admin.pagination :paginator="$logs" />
@endif
@endsection
