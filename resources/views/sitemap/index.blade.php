{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <url>
        <loc>{{ route('home') }}</loc>
        @if ($latestUpdate)<lastmod>{{ \Illuminate\Support\Carbon::parse($latestUpdate)->toAtomString() }}</lastmod>@endif
    </url>
    <url>
        <loc>{{ route('blog.index') }}</loc>
    </url>
@foreach ($categories as $category)
    <url>
        <loc>{{ route('categories.show', $category->slug) }}</loc>
        <lastmod>{{ $category->updated_at?->toAtomString() }}</lastmod>
    </url>
@endforeach
@foreach ($products as $product)
    <url>
        <loc>{{ route('products.show', $product->slug) }}</loc>
        <lastmod>{{ $product->updated_at?->toAtomString() }}</lastmod>
@foreach ($product->images->concat($product->schematics) as $image)
        <image:image>
            <image:loc>{{ $image->url() }}</image:loc>
        </image:image>
@endforeach
    </url>
@endforeach
@foreach ($posts as $post)
    <url>
        <loc>{{ route('blog.show', $post->slug) }}</loc>
        <lastmod>{{ $post->updated_at?->toAtomString() }}</lastmod>
@foreach ($post->featuredImage as $image)
        <image:image>
            <image:loc>{{ $image->url() }}</image:loc>
        </image:image>
@endforeach
    </url>
@endforeach
@foreach ($pages as $page)
    <url>
        <loc>{{ route('pages.show', $page->slug) }}</loc>
        <lastmod>{{ $page->updated_at?->toAtomString() }}</lastmod>
    </url>
@endforeach
@foreach ($tags as $tag)
    <url>
        <loc>{{ route('blog.tag', $tag->slug) }}</loc>
    </url>
@endforeach
</urlset>
