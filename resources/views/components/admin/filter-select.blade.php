@props(['name', 'label', 'options'])

{{-- A filter dropdown that applies itself on change; $options is value => label. --}}
<select name="{{ $name }}" aria-label="{{ $label }}" onchange="this.form.requestSubmit()"
        @class([
            'rounded-lg border py-2 pl-8 pr-3 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/20',
            'border-brand-accent bg-orange-50' => filled(request($name)),
            'border-neutral-200 bg-white' => blank(request($name)),
        ])>
    <option value="">{{ $label }}: همه</option>
    @foreach ($options as $value => $optionLabel)
        <option value="{{ $value }}" @selected((string) request($name) === (string) $value)>{{ $optionLabel }}</option>
    @endforeach
</select>
