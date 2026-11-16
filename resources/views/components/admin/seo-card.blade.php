@props([
    'metaTitle' => '',
    'metaDescription' => '',
    'focusKeyword' => null,
    'withFocusKeyword' => false,
    'urlPrefix' => '',
    'slug' => '',
    'fallbackTitleField' => 'title',
])

{{-- SEO fields with recommended lengths and a live search-result preview. --}}
<div class="admin-card" x-data="{
        seoTitle: @js((string) $metaTitle),
        seoDescription: @js((string) $metaDescription),
        fallbackTitle() {
            return document.querySelector('[name={{ $fallbackTitleField }}]')?.value ?? '';
        },
        fallbackSlug() {
            return document.querySelector('[name=slug]')?.value || @js((string) $slug) || '…';
        },
    }">
    <div class="admin-card-header">
        <h2 class="admin-card-title">سئو (نمایش در نتایج گوگل)</h2>
        <span class="text-xs text-neutral-500">اختیاری — خالی بماند، از عنوان و خلاصه استفاده می‌شود</span>
    </div>
    <div class="admin-card-body">
        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4" aria-label="پیش‌نمایش نتیجه جستجو">
            <p class="mb-1 truncate text-xs text-neutral-500" dir="ltr" x-text="@js($urlPrefix) + decodeURIComponent(fallbackSlug())"></p>
            <p class="truncate text-lg leading-7 text-[#1a0dab]" x-text="(seoTitle || fallbackTitle() || 'عنوان صفحه')"></p>
            <p class="line-clamp-2 text-sm leading-6 text-neutral-600" x-text="seoDescription || 'توضیحات متا اینجا نمایش داده می‌شود…'"></p>
        </div>

        <x-admin.counted-field name="meta_title" label="عنوان سئو (Meta title)" :value="$metaTitle" :min="30" :max="60" model="seoTitle"
                               hint="گوگل حدود ۶۰ نویسه‌ی اول را نشان می‌دهد. کلمه‌ی کلیدی را نزدیک ابتدای عنوان بیاورید." />

        <x-admin.counted-field name="meta_description" label="توضیحات متا (Meta description)" :value="$metaDescription" :min="120" :max="160" textarea :rows="3" model="seoDescription"
                               hint="خلاصه‌ای جذاب بین ۱۲۰ تا ۱۶۰ نویسه که کاربر را به کلیک ترغیب کند." />

        @if ($withFocusKeyword)
            <div>
                <label for="field-focus_keyword" class="admin-label">کلمه کلیدی کانونی</label>
                <input id="field-focus_keyword" name="focus_keyword" value="{{ $focusKeyword }}" class="admin-input" placeholder="مثلاً برس سیمی صنعتی">
            </div>
        @endif
    </div>
</div>
