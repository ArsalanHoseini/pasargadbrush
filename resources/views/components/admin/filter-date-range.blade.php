@props(['label' => 'تاریخ'])

{{-- Shamsi from/to inputs (e.g. ۱۴۰۵/۰۸/۰۱); a date that cannot be read is ignored and marked. --}}
@foreach (['from' => 'از', 'to' => 'تا'] as $name => $prefix)
    @php $isInvalid = filled(request($name)) && ! \App\Support\PersianDate::parse(request($name)); @endphp
    <input type="text" name="{{ $name }}" value="{{ request($name) }}" inputmode="numeric" dir="ltr"
           placeholder="{{ $prefix }} ۱۴۰۵/۰۸/۰۱" aria-label="{{ $label }} {{ $prefix }}"
           @if ($isInvalid) aria-invalid="true" title="تاریخ نامعتبر است و نادیده گرفته شد." @endif
           @class([
               'w-32 rounded-lg border px-3 py-2 text-center text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/20',
               'border-red-400 bg-red-50' => $isInvalid,
               'border-brand-accent bg-orange-50' => ! $isInvalid && filled(request($name)),
               'border-neutral-200 bg-white' => blank(request($name)),
           ])>
@endforeach
