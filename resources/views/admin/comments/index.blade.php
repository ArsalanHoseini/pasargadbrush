@extends('admin.layout')

@section('title', 'دیدگاه‌ها')

@php
$statusLabels = ['pending' => 'در انتظار تأیید', 'approved' => 'تأییدشده', 'spam' => 'اسپم'];
@endphp

@section('content')
<x-admin.page-header title="دیدگاه‌ها" />

<div class="mb-6 flex gap-2 text-sm">
    <a href="{{ route('admin.comments.index', ['status' => 'all']) }}"
       class="rounded px-3 py-1.5 {{ $status === 'all' ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">همه</a>
    @foreach ($statusLabels as $value => $label)
        <a href="{{ route('admin.comments.index', ['status' => $value]) }}"
           class="rounded px-3 py-1.5 {{ $status === $value ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if ($comments->isEmpty())
    <p class="text-sm text-neutral-500">دیدگاهی ثبت نشده است.</p>
@else
<div x-data="bulkSelection">
    <label class="mb-3 flex items-center gap-2 text-sm text-neutral-600"><x-admin.bulk-select-all /> انتخاب همه‌ی این صفحه</label>
    <div class="space-y-4">
        @foreach ($comments as $comment)
            <div class="rounded-lg border border-neutral-200 bg-white p-4 {{ $comment->status === 'pending' ? 'bg-amber-50' : '' }}">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-xs text-neutral-500">
                    <span class="flex items-center gap-2">
                        <x-admin.bulk-checkbox :value="$comment->id" :label="'انتخاب '.$comment->author_name" />
                        <span>
                        <strong class="text-neutral-800">{{ $comment->author_name }}</strong>
                        ({{ $comment->author_email }}) — روی
                        @if ($comment->post)
                            <a href="{{ route('blog.show', $comment->post) }}" class="underline" target="_blank">{{ $comment->post->title }}</a>
                        @else
                            مقاله حذف‌شده
                        @endif
                        </span>
                    </span>
                    <span>{{ persian_date($comment->created_at, \App\Support\PersianDate::COMPACT) }} — {{ $statusLabels[$comment->status] }}</span>
                </div>

                <p class="mb-3 text-sm text-neutral-700">{{ $comment->body }}</p>

                <div class="flex gap-3 text-xs">
                    @if ($comment->status !== 'approved')
                        <form method="POST" action="{{ route('admin.comments.approve', $comment) }}">
                            @csrf
                            <button type="submit" class="text-green-700 hover:underline">تأیید</button>
                        </form>
                    @endif
                    @if ($comment->status !== 'spam')
                        <form method="POST" action="{{ route('admin.comments.spam', $comment) }}">
                            @csrf
                            <button type="submit" class="text-amber-700 hover:underline">اسپم</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}"
                          data-confirm="حذف این دیدگاه قطعی است. ادامه می‌دهید؟">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-700 hover:underline">حذف</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <x-admin.bulk-bar :action="route('admin.comments.bulk')" :actions="['approve' => 'تأیید', 'spam' => 'اسپم', 'delete' => 'حذف']" />
</div>

    <x-admin.pagination :paginator="$comments" />
@endif
@endsection
