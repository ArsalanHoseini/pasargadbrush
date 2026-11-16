@extends('admin.layout')

@section('title', 'تنظیمات سایت')

@section('content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">تنظیمات سایت</h1>

<x-admin.form-errors />

<form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-2xl space-y-6">
    @csrf
    @method('PUT')

    @foreach ($groups as $groupName => $fields)
        <div class="admin-card p-6">
            <h2 class="mb-4 text-sm font-medium text-neutral-500">{{ $groupName }}</h2>
            <div class="space-y-3">
                @foreach ($fields as $key => $field)
                    <div>
                        <label class="admin-label">{{ $field['label'] }}</label>
                        <input name="{{ $key }}" value="{{ old($key, $field['value']) }}"
                               @if (str_starts_with($key, 'social_') || str_starts_with($key, 'map_')) dir="ltr" placeholder="https://" @endif
                               @if (in_array($key, ['contact_whatsapp', 'contact_bale'], true)) dir="ltr" @endif
                               class="admin-input">
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">
        ذخیره تنظیمات
    </button>
</form>
@endsection
