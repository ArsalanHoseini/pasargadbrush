<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\Product;
use App\Support\SiteProfile;
use Illuminate\Support\Str;

/**
 * schema.org nodes for the JSON-LD graph (see SchemaGraph). Every node has a
 * stable «@id» (page URL + fragment) and points at the others by it. Facts
 * come from SiteProfile and the models — empty values are left out, never
 * filled with a placeholder.
 */
class StructuredData
{
    public const WEBPAGE_FRAGMENT = '#webpage';

    public static function organizationId(): string
    {
        return SiteProfile::url().'/#organization';
    }

    public static function webSiteId(): string
    {
        return SiteProfile::url().'/#website';
    }

    public static function catalogId(): string
    {
        return SiteProfile::url().'/#catalog';
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $contact = SiteProfile::contact();
        $phone = SiteProfile::internationalPhone($contact['mobile']);

        return self::clean([
            '@type' => 'Organization',
            '@id' => self::organizationId(),
            'name' => SiteProfile::name(),
            'legalName' => SiteProfile::legalName(),
            'alternateName' => config('site.alternate_names'),
            'url' => SiteProfile::url().'/',
            'logo' => [
                '@type' => 'ImageObject',
                '@id' => SiteProfile::url().'/#logo',
                'url' => SiteProfile::logoUrl(),
                'caption' => SiteProfile::name(),
            ],
            'image' => ['@id' => SiteProfile::url().'/#logo'],
            'description' => SiteProfile::description(),
            'email' => $contact['email'],
            'telephone' => $phone,
            'address' => self::clean([
                '@type' => 'PostalAddress',
                'streetAddress' => $contact['address'],
                'addressLocality' => config('site.address.locality'),
                'addressRegion' => config('site.address.region'),
                'addressCountry' => config('site.address.country'),
            ]),
            'contactPoint' => $phone || $contact['email'] ? [self::clean([
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'telephone' => $phone,
                'email' => $contact['email'],
                'areaServed' => config('site.address.country'),
                'availableLanguage' => ['fa'],
            ])] : null,
            'hasMap' => $contact['map_url'],
            'sameAs' => array_values(SiteProfile::socialLinks()) ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function webSite(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::webSiteId(),
            'url' => SiteProfile::url().'/',
            'name' => SiteProfile::name(),
            'alternateName' => config('site.alternate_names'),
            'description' => SiteProfile::description(),
            'inLanguage' => config('site.language'),
            'publisher' => ['@id' => self::organizationId()],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('search.index').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  string  $type  WebPage, AboutPage, ContactPage, CollectionPage, ItemPage …
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function webPage(string $type, string $url, string $name, ?string $description = null, bool $hasBreadcrumb = false, array $extra = []): array
    {
        return self::clean([
            '@type' => $type,
            '@id' => $url.self::WEBPAGE_FRAGMENT,
            'url' => $url,
            'name' => html_entity_decode($name),
            'description' => $description ? html_entity_decode($description) : null,
            'inLanguage' => config('site.language'),
            'isPartOf' => ['@id' => self::webSiteId()],
            'about' => ['@id' => self::organizationId()],
            'breadcrumb' => $hasBreadcrumb ? ['@id' => $url.'#breadcrumb'] : null,
            ...$extra,
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items  [name, url] from the homepage down to the current page.
     * @return array<string, mixed>
     */
    public static function breadcrumbs(string $pageUrl, array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $pageUrl.'#breadcrumb',
            'itemListElement' => array_map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item[0],
                'item' => $item[1],
            ], $items, array_keys($items)),
        ];
    }

    /**
     * The product categories as the company's catalog: a node that adds
     * «hasOfferCatalog» to the Organization (same «@id», merged by parsers).
     *
     * @param  list<array{name: string, slug: string}>  $categories  As ProductCategory::navigation() returns them.
     * @return array<string, mixed>
     */
    public static function catalog(array $categories): array
    {
        return [
            '@id' => self::organizationId(),
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                '@id' => self::catalogId(),
                'name' => 'محصولات '.SiteProfile::name(),
                'url' => route('home').'#products',
                'itemListElement' => array_map(fn (array $category) => [
                    '@type' => 'OfferCatalog',
                    'name' => $category['name'],
                    'url' => route('categories.show', $category['slug']),
                ], array_values($categories)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function blogPosting(BlogPost $post): array
    {
        $url = route('blog.show', $post->slug);
        $image = $post->featuredImage->first();
        $author = $post->author
            ? ['@type' => 'Person', 'name' => $post->author->name]
            : ['@id' => self::organizationId()];

        return self::clean([
            '@type' => 'BlogPosting',
            '@id' => $url.'#article',
            'headline' => Str::limit($post->title, 110, ''),
            'description' => PostMarkdown::description($post),
            'url' => $url,
            'mainEntityOfPage' => ['@id' => $url.self::WEBPAGE_FRAGMENT],
            'isPartOf' => ['@id' => $url.self::WEBPAGE_FRAGMENT],
            'image' => $image ? self::clean([
                '@type' => 'ImageObject',
                'url' => $image->url(),
                'width' => $image->width,
                'height' => $image->height,
            ]) : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toIso8601String(),
            'author' => $author,
            'publisher' => ['@id' => self::organizationId()],
            'articleSection' => $post->category?->name,
            'keywords' => $post->tags->pluck('name')->implode('، ') ?: null,
            'wordCount' => PostMarkdown::wordCount($post),
            'timeRequired' => $post->reading_time ? 'PT'.$post->reading_time.'M' : null,
            'inLanguage' => config('site.language'),
            'encoding' => [
                '@type' => 'MediaObject',
                'contentUrl' => route('blog.markdown', $post->slug),
                'encodingFormat' => 'text/markdown',
            ],
        ]);
    }

    /**
     * Fixed-price products get their offers in Rial (IRR); quote-only ones
     * have no price to state, so no offer.
     *
     * @return array<string, mixed>
     */
    public static function product(Product $product): array
    {
        $url = route('products.show', $product->slug);
        $prices = $product->type === 'priced'
            ? $product->variants->pluck('price_rial')->filter()->map(fn ($price) => (int) $price)
            : collect();
        $availability = $product->stock_status === 'out_of_stock' ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock';

        $offers = match (true) {
            $prices->isEmpty() => null,
            $prices->count() === 1 => [
                '@type' => 'Offer',
                'price' => $prices->first(),
                'priceCurrency' => 'IRR',
                'availability' => $availability,
                'url' => $url,
                'seller' => ['@id' => self::organizationId()],
            ],
            default => [
                '@type' => 'AggregateOffer',
                'lowPrice' => $prices->min(),
                'highPrice' => $prices->max(),
                'offerCount' => $prices->count(),
                'priceCurrency' => 'IRR',
                'availability' => $availability,
                'url' => $url,
                'seller' => ['@id' => self::organizationId()],
            ],
        };

        return self::clean([
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => $product->name,
            'sku' => $product->sku,
            'url' => $url,
            'mainEntityOfPage' => ['@id' => $url.self::WEBPAGE_FRAGMENT],
            'image' => $product->images->map(fn ($image) => $image->url())->values()->all() ?: null,
            'description' => Str::limit(Str::squish(html_entity_decode(strip_tags($product->short_description ?: (string) $product->description))), 5000) ?: null,
            'category' => $product->primaryCategory?->name,
            'brand' => ['@type' => 'Brand', 'name' => SiteProfile::name()],
            'manufacturer' => ['@id' => self::organizationId()],
            'offers' => $offers,
        ]);
    }

    /**
     * Drops null/empty values so no property is published without a real value.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function clean(array $node): array
    {
        return array_filter($node, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}
