@props(['name', 'value' => ''])

<div x-data="richTextEditor(@js($value))" dir="rtl">
    <div x-ref="editor" class="min-h-62.5 rounded-b border border-t-0 border-neutral-300 bg-white text-sm"></div>
    <textarea x-ref="input" name="{{ $name }}" class="hidden">{{ $value }}</textarea>
</div>
