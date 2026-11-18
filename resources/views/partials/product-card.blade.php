{{-- One product card for every listing (home, category, search) so they all line up the same way. --}}
@php($headingTag = $headingTag ?? 'h3')
<a href="{{ route('products.show', $product) }}"
   class="group flex h-full flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:border-neutral-300 hover:shadow-lg hover:shadow-neutral-200/60">
    <div class="aspect-square overflow-hidden bg-neutral-100">
        @if ($product->images->isNotEmpty())
            <img src="{{ $product->images->first()->thumbnailUrl(480) }}" alt="" width="480" height="480"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
        @else
            <div class="flex h-full w-full items-center justify-center text-xs text-neutral-500">بدون تصویر</div>
        @endif
    </div>
    <div class="flex flex-1 flex-col border-t border-neutral-100 p-3">
        <{{ $headingTag }} class="line-clamp-2 min-h-10 text-sm font-medium leading-5 text-neutral-900 group-hover:text-brand-accent-dark">{{ $product->name }}</{{ $headingTag }}>
        @if ($product->primaryCategory)
            <p class="mt-auto pt-1 text-xs text-neutral-500">{{ $product->primaryCategory->name }}</p>
        @endif
    </div>
</a>
