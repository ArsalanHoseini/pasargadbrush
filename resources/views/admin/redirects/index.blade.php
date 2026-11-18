@extends('admin.layout')

@section('title', 'ریدایرکت‌ها')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <div>
        <h1 class="text-lg font-semibold">ریدایرکت‌ها (انتقال آدرس‌ها)</h1>
        <p class="text-xs text-neutral-500">
            آدرس‌های قدیمی که دیگر وجود ندارند به آدرس جدید منتقل می‌شوند تا رتبه‌ی گوگل و لینک‌های قدیمی از دست نروند.
            فقط وقتی اجرا می‌شوند که آدرس در سایت جدید وجود نداشته باشد.
        </p>
    </div>
    <a href="{{ route('admin.redirects.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">+ ریدایرکت جدید</a>
</div>

<div class="mb-4 flex flex-wrap gap-2 text-xs">
    <a href="{{ route('admin.redirects.index', array_filter(['q' => $search])) }}"
       class="rounded px-3 py-1.5 {{ ! $source ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">همه</a>
    @foreach (\App\Models\SeoRedirect::SOURCE_LABELS as $value => $label)
        <a href="{{ route('admin.redirects.index', array_filter(['source' => $value, 'q' => $search])) }}"
           class="rounded px-3 py-1.5 {{ $source === $value ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">
            {{ $label }} ({{ $totals[$value]->total ?? 0 }} — {{ (int) ($totals[$value]->hits ?? 0) }} بازدید)
        </a>
    @endforeach
</div>

<form method="GET" class="mb-4 flex gap-2">
    @if ($source)<input type="hidden" name="source" value="{{ $source }}">@endif
    <label for="q" class="sr-only">جستجو</label>
    <input id="q" name="q" value="{{ $search }}" placeholder="جستجو در آدرس قدیمی یا جدید"
           class="w-full max-w-sm admin-input">
    <button type="submit" class="rounded border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">جستجو</button>
</form>

@if ($redirects->isEmpty())
    <p class="text-sm text-neutral-500">ریدایرکتی پیدا نشد.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">آدرس قدیمی</th>
                    <th class="px-4 py-3 font-medium">آدرس جدید</th>
                    <th class="px-4 py-3 font-medium">نوع</th>
                    <th class="px-4 py-3 font-medium">بازدید</th>
                    <th class="px-4 py-3 font-medium">منبع</th>
                    <th class="px-4 py-3"><span class="sr-only">عملیات</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($redirects as $redirect)
                    <tr @class(['opacity-50' => ! $redirect->is_active])>
                        <td class="max-w-xs truncate px-4 py-2 text-xs" dir="ltr" title="{{ $redirect->from_path }}">{{ $redirect->from_path }}</td>
                        <td class="max-w-xs truncate px-4 py-2 text-xs" dir="ltr" title="{{ $redirect->to_path }}">
                            {{ $redirect->to_path ?? '—' }}
                        </td>
                        <td class="px-4 py-2 text-xs">{{ $redirect->status_code === 410 ? '410 (حذف‌شده)' : $redirect->status_code }}</td>
                        <td class="px-4 py-2 text-xs">{{ $redirect->hits_count }}</td>
                        <td class="px-4 py-2 text-xs text-neutral-500">{{ \App\Models\SeoRedirect::SOURCE_LABELS[$redirect->source] ?? $redirect->source }}</td>
                        <td class="px-4 py-2 text-left text-xs">
                            <a href="{{ route('admin.redirects.edit', $redirect) }}" class="text-blue-700 hover:underline">ویرایش</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-admin.pagination :paginator="$redirects" />
@endif
@endsection
