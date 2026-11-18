@props(['name', 'value' => ''])

<div x-data="richTextEditor(@js($value))" dir="rtl" class="admin-editor">
    <div x-ref="editor" class="bg-white"></div>
    <textarea x-ref="input" name="{{ $name }}" class="hidden">{{ $value }}</textarea>
    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
        <span><span x-text="words.toLocaleString('fa-IR')">۰</span> کلمه</span>
        <span><span x-text="characters.toLocaleString('fa-IR')">۰</span> نویسه</span>
        <span>
            تیترها:
            <span :class="headingCount.h1 > 0 && 'font-medium text-amber-700'">H1 × <span x-text="headingCount.h1.toLocaleString('fa-IR')"></span></span>،
            H2 × <span x-text="headingCount.h2.toLocaleString('fa-IR')"></span>،
            H3 × <span x-text="headingCount.h3.toLocaleString('fa-IR')"></span>
        </span>
        <span x-show="headingCount.h1 > 0" x-cloak class="text-amber-700">عنوان صفحه خودش H1 است؛ برای بخش‌های متن بهتر است از H2 و H3 استفاده کنید.</span>
    </div>
</div>
