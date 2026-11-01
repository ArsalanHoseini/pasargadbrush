@props(['product', 'withNotes' => true])

{{--
    The product's schematic drawing followed by one input per dimension the
    staff defined for it (see App\Services\Customization\DimensionForm for the
    matching validation). Each input is labeled with the letter that marks
    that dimension on the drawing.
--}}
<div class="space-y-4">
    @if ($product->schematic)
        <figure class="overflow-hidden rounded-lg border border-neutral-200 bg-white p-3">
            <img src="{{ $product->schematic->url() }}" alt="شماتیک ابعاد {{ $product->name }}"
                 class="mx-auto max-h-80 w-auto object-contain">
            <figcaption class="mt-2 text-center text-xs text-neutral-500">
                حروف روی شکل را با فیلدهای زیر تطبیق دهید و اندازه‌ی هر بخش را دقیق وارد کنید.
            </figcaption>
        </figure>
    @endif

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($product->dimensionFields as $field)
            @php $inputId = 'dimension-'.$field->id; @endphp
            <div>
                <label for="{{ $inputId }}" class="mb-1 flex items-center gap-2 text-sm font-medium text-neutral-800">
                    <span class="inline-flex min-w-7 items-center justify-center rounded bg-neutral-900 px-1.5 py-0.5 text-xs font-bold text-white" dir="ltr">{{ $field->code }}</span>
                    <span>{{ $field->label }}</span>
                    @if ($field->is_required)
                        <span class="text-red-600" aria-hidden="true">*</span>
                    @else
                        <span class="text-xs font-normal text-neutral-400">(اختیاری)</span>
                    @endif
                </label>
                <div class="flex items-stretch overflow-hidden rounded border border-neutral-300 focus-within:border-neutral-900 @error('dimensions.'.$field->id) border-red-400 @enderror">
                    <input id="{{ $inputId }}" name="dimensions[{{ $field->id }}]" inputmode="decimal" dir="ltr"
                           value="{{ old('dimensions.'.$field->id) }}" @required($field->is_required)
                           class="w-full min-w-0 px-3 py-2 text-sm outline-none">
                    <span class="flex shrink-0 items-center bg-neutral-50 px-3 text-xs text-neutral-500">{{ $field->unitLabel() }}</span>
                </div>
                @if ($field->min_value !== null || $field->max_value !== null)
                    <p class="mt-1 text-xs text-neutral-500">
                        @if ($field->min_value !== null && $field->max_value !== null)
                            بین {{ $field->min_value + 0 }} تا {{ $field->max_value + 0 }} {{ $field->unitLabel() }}
                        @elseif ($field->min_value !== null)
                            حداقل {{ $field->min_value + 0 }} {{ $field->unitLabel() }}
                        @else
                            حداکثر {{ $field->max_value + 0 }} {{ $field->unitLabel() }}
                        @endif
                    </p>
                @endif
                @error('dimensions.'.$field->id)
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>

    @if ($withNotes)
    <div>
        <label for="customization_notes" class="mb-1 block text-sm font-medium text-neutral-800">توضیحات تکمیلی (اختیاری)</label>
        <textarea id="customization_notes" name="customization_notes" rows="3"
                  placeholder="مثلاً جنس یا رنگ موی برس، نوع کاربرد، یا هر نکته‌ی دیگری درباره‌ی ساخت"
                  class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('customization_notes') }}</textarea>
    </div>
    @endif
</div>
