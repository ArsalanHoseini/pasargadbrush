@props(['action', 'actions'])

{{-- Floating bar for the rows ticked inside the surrounding x-data="bulkSelection"; $actions is value => label. --}}
<form id="bulk-form" method="POST" action="{{ $action }}" x-show="selected.length" x-cloak x-transition.opacity
      data-confirm="این عملیات روی موارد انتخاب‌شده انجام شود؟"
      class="sticky bottom-4 z-20 mt-4 flex flex-wrap items-center gap-3 rounded-xl border border-neutral-200 bg-brand-charcoal px-4 py-3 text-sm text-white shadow-lg">
    @csrf
    <span><span x-text="selected.length"></span> مورد انتخاب شد</span>
    <select name="action" required aria-label="عملیات گروهی" class="rounded-lg border-0 bg-white/10 py-1.5 pl-8 pr-3 text-sm text-white">
        <option value="" class="text-neutral-900">انتخاب عملیات…</option>
        @foreach ($actions as $value => $label)
            <option value="{{ $value }}" class="text-neutral-900">{{ $label }}</option>
        @endforeach
    </select>
    {{ $slot }}
    <button type="submit" class="rounded-lg bg-brand-accent-dark px-4 py-1.5 font-medium hover:bg-brand-accent-darker">اجرا</button>
    <button type="button" @click="selected = []" class="mr-auto text-white/70 hover:text-white">لغو انتخاب</button>
</form>
