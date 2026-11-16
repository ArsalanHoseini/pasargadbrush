@props([
    'name',
    'label',
    'value' => '',
    'min' => 0,
    'max' => 160,
    'textarea' => false,
    'rows' => 3,
    'hint' => null,
    'placeholder' => null,
    'required' => false,
    'model' => null,
])

{{-- A text field with a live character count against a recommended range (SEO titles, descriptions, excerpts). --}}
<div x-data="charCounter(@js((string) $value), {{ (int) $min }}, {{ (int) $max }})" {{ $attributes->only('class') }}
     @if ($model) x-effect="{{ $model }} = value" @endif>
    <div class="mb-1.5 flex items-end justify-between gap-3">
        <label for="field-{{ $name }}" class="text-sm font-medium text-neutral-800">
            {{ $label }}
            @if ($required)<span class="text-red-600">*</span>@endif
        </label>
        <span class="shrink-0 text-xs tabular-nums"
              :class="{
                  'text-neutral-400': state === 'empty',
                  'text-amber-600': state === 'short',
                  'text-green-700': state === 'good',
                  'text-red-600': state === 'long',
              }">
            <span x-text="length.toLocaleString('fa-IR')">{{ mb_strlen((string) $value) }}</span>
            @if ($min > 0)
                / پیشنهادی {{ \App\Support\PersianDigits::toPersian((string) $min) }}–{{ \App\Support\PersianDigits::toPersian((string) $max) }} نویسه
            @else
                / حداکثر {{ \App\Support\PersianDigits::toPersian((string) $max) }} نویسه
            @endif
        </span>
    </div>
    @if ($textarea)
        <textarea id="field-{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" x-model="value"
                  @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($required)
                  class="admin-input">{{ $value }}</textarea>
    @else
        <input id="field-{{ $name }}" name="{{ $name }}" value="{{ $value }}" x-model="value"
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($required)
               class="admin-input">
    @endif
    <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-neutral-100" aria-hidden="true">
        <div class="h-full rounded-full transition-all"
             :style="`width: ${Math.min(100, (length / {{ (int) $max }}) * 100)}%`"
             :class="{
                 'bg-amber-400': state === 'short',
                 'bg-green-500': state === 'good',
                 'bg-red-500': state === 'long',
             }"></div>
    </div>
    @if ($hint)
        <p class="admin-hint">{{ $hint }}</p>
    @endif
    @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
