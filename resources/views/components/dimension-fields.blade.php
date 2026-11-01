@props(['product', 'withNotes' => true])

{{--
    A product's order form fields, as rows: numeric dimensions (title, the
    letter that marks it on the schematic drawings, input, unit) and choices
    from a fixed list shown as selectable chips (e.g. «جنس الیاف»). Validation:
    App\Services\Customization\DimensionForm.
--}}
<div class="space-y-3">
    @foreach ($product->dimensionFields as $field)
        @php
            $inputId = 'dimension-'.$field->id;
            $errorKey = 'dimensions.'.$field->id;
        @endphp

        @if ($field->isChoice())
            <fieldset class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-neutral-100 pt-3">
                <legend class="sr-only">{{ $field->label }}</legend>
                <span aria-hidden="true" class="w-32 shrink-0 text-sm font-medium text-neutral-800">
                    @if ($field->is_required)<span class="text-red-600">*</span>@endif
                    {{ $field->label }}
                </span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($field->options ?? [] as $option)
                        <label class="cursor-pointer">
                            <input type="radio" name="dimensions[{{ $field->id }}]" value="{{ $option }}" class="peer sr-only"
                                   @checked(old('dimensions.'.$field->id) === $option) @required($field->is_required)>
                            <span class="block rounded-lg border border-neutral-300 px-4 py-2 text-sm text-neutral-700 transition peer-checked:border-brand-charcoal peer-checked:bg-brand-charcoal peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-accent hover:border-neutral-500">
                                {{ $option }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error($errorKey)
                    <p class="w-full text-xs text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>
        @else
            <div>
                <div class="flex items-center gap-3">
                    <label for="{{ $inputId }}" class="flex w-32 shrink-0 items-center gap-1 text-sm font-medium text-neutral-800 sm:w-40">
                        @if ($field->is_required)<span class="text-red-600" aria-hidden="true">*</span>@endif
                        <span>{{ $field->label }}</span>
                        @unless ($field->is_required)<span class="text-xs font-normal text-neutral-400">(اختیاری)</span>@endunless
                    </label>
                    <span class="w-10 shrink-0 text-center text-base font-bold text-neutral-900" dir="ltr" aria-hidden="true">{{ $field->code }}</span>
                    <input id="{{ $inputId }}" name="dimensions[{{ $field->id }}]" inputmode="decimal" dir="ltr"
                           value="{{ old('dimensions.'.$field->id) }}" @required($field->is_required)
                           @if ($field->min_value !== null || $field->max_value !== null)
                               placeholder="{{ $field->min_value !== null ? $field->min_value + 0 : '' }}{{ $field->min_value !== null && $field->max_value !== null ? ' تا ' : '' }}{{ $field->max_value !== null ? $field->max_value + 0 : '' }}"
                           @endif
                           aria-describedby="{{ $inputId }}-unit"
                           @class([
                               'min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm outline-none focus:border-brand-charcoal',
                               'border-red-400' => $errors->has($errorKey),
                               'border-neutral-300' => ! $errors->has($errorKey),
                           ])>
                    <span id="{{ $inputId }}-unit" class="w-16 shrink-0 text-xs text-neutral-500">{{ $field->unitLabel() }}</span>
                </div>
                @error($errorKey)
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif
    @endforeach

    @if ($withNotes)
        <details class="border-t border-neutral-100 pt-3" @if (old('customization_notes')) open @endif>
            <summary class="cursor-pointer text-sm text-neutral-600">توضیحات تکمیلی (اختیاری)</summary>
            <label for="customization_notes" class="sr-only">توضیحات تکمیلی</label>
            <textarea id="customization_notes" name="customization_notes" rows="3"
                      placeholder="مثلاً رنگ الیاف، نوع کاربرد، یا هر نکته‌ی دیگری درباره‌ی ساخت"
                      class="mt-2 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ old('customization_notes') }}</textarea>
        </details>
    @endif
</div>
