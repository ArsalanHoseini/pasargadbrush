@extends('admin.layout')

@section('title', 'تنظیمات سایت')

@section('content')
<h1 class="mb-6 text-lg font-semibold">تنظیمات سایت</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-2xl space-y-6">
    @csrf
    @method('PUT')

    @foreach ($groups as $groupName => $fields)
        <div class="rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-medium text-neutral-500">{{ $groupName }}</h2>
            <div class="space-y-3">
                @foreach ($fields as $key => $field)
                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ $field['label'] }}</label>
                        <input name="{{ $key }}" value="{{ old($key, $field['value']) }}"
                               @if (str_starts_with($key, 'social_')) dir="ltr" placeholder="https://" @endif
                               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        ذخیره تنظیمات
    </button>
</form>
@endsection
