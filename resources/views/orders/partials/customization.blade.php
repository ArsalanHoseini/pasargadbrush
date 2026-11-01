{{--
    A line's customer dimensions snapshot (see DimensionForm::snapshot()).
    @param array|null $customization
--}}
@if (! empty($customization))
    <dl class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-neutral-600">
        @foreach ($customization['dimensions'] ?? [] as $dimension)
            <div class="flex gap-1">
                <dt><span dir="ltr" class="font-semibold">{{ $dimension['code'] }}</span> {{ $dimension['label'] }}:</dt>
                <dd><span dir="ltr">{{ $dimension['value'] }}</span> {{ \App\Models\ProductDimensionField::UNITS[$dimension['unit']] ?? $dimension['unit'] }}</dd>
            </div>
        @endforeach
    </dl>
    @if (! empty($customization['notes']))
        <p class="mt-1 text-xs text-neutral-500">توضیحات: {{ $customization['notes'] }}</p>
    @endif
@endif
