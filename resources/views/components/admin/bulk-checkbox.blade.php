@props(['value', 'label' => 'انتخاب'])

<input type="checkbox" name="ids[]" value="{{ $value }}" form="bulk-form" data-bulk-id x-model="selected"
       aria-label="{{ $label }}" class="size-4 rounded border-neutral-300 accent-brand-accent-dark">
