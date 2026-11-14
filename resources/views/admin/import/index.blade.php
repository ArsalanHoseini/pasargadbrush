@extends('admin.layout')

@section('title', 'ایمپورت از وردپرس')

@section('content')
<h1 class="mb-2 text-lg font-semibold">ایمپورت از وردپرس</h1>
<p class="mb-6 text-sm text-neutral-500">
    این ابزار مستقیماً از دیتابیس محلی وردپرس (نسخه‌ای که به سایت اصلی دست نمی‌زند) داده‌ها را می‌خواند.
    ترتیب اجرا مهم است: اول دسته‌بندی محصولات، بعد محصولات؛ اول دسته‌بندی بلاگ، بعد مقالات.
    هر مرحله را می‌توان چند بار اجرا کرد — موارد قبلاً وارد شده دوباره وارد نمی‌شوند.
</p>

<div class="space-y-4">
    @foreach ($steps as $key => $step)
        @php($last = $lastResults[$key] ?? null)
        <div class="rounded-lg border border-neutral-200 bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-medium">{{ $step['label'] }}</h2>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.import.run', $key) }}">
                        @csrf
                        <button type="submit" class="rounded border border-neutral-300 px-3 py-1.5 text-sm hover:bg-neutral-50">
                            پیش‌نمایش (Dry Run)
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.import.run', $key) }}"
                          data-confirm="این مرحله واقعاً در دیتابیس نوشته می‌شود. ادامه می‌دهید؟">
                        @csrf
                        <input type="hidden" name="commit" value="1">
                        <button type="submit" class="rounded bg-neutral-900 px-3 py-1.5 text-sm text-white hover:bg-neutral-700">
                            اجرای واقعی
                        </button>
                    </form>
                </div>
            </div>

            @if ($last)
                <div class="mt-4 rounded border border-neutral-200 bg-neutral-50 p-3 text-sm">
                    <p class="mb-2 font-medium">
                        آخرین اجرا ({{ $last['commit'] ? 'واقعی' : 'پیش‌نمایش' }}) — {{ $last['ran_at'] }}:
                        وارد شده: {{ $last['imported'] }}، رد شده (تکراری): {{ $last['skipped'] }}، خطا: {{ $last['failed'] }}
                    </p>
                    <details>
                        <summary class="cursor-pointer text-neutral-600">نمایش جزئیات ({{ count($last['messages']) }} مورد)</summary>
                        <ul class="mt-2 max-h-64 space-y-1 overflow-y-auto text-xs text-neutral-600">
                            @foreach ($last['messages'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </details>
                </div>
            @endif
        </div>
    @endforeach
</div>
@endsection
