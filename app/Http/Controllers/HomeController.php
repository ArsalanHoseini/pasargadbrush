<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The «انواع برس» strip of the previous WordPress homepage: label, product
     * SKU it links to, and its picture in public/images/brush-types.
     *
     * @var array<int, array{name: string, sku: string, image: string}>
     */
    private const BRUSH_TYPES = [
        ['name' => 'برس سیلندری', 'sku' => 'WP-30710', 'image' => 'cylindrical.webp'],
        ['name' => 'برس دیسکی', 'sku' => 'WP-30749', 'image' => 'disc.webp'],
        ['name' => 'برس نواری', 'sku' => 'WP-30750', 'image' => 'flat-strip.webp'],
        ['name' => 'برس روتاری', 'sku' => 'WP-30753', 'image' => 'rotary.webp'],
        ['name' => 'برس استریپ', 'sku' => 'WP-30762', 'image' => 'strip.webp'],
        ['name' => 'برس استریپ رول', 'sku' => 'WP-30751', 'image' => 'strip-roll.webp'],
    ];

    public function index(): View
    {
        return view('home', [
            'brushTypes' => $this->brushTypes(),
            'products' => Product::query()
                ->with(['images', 'primaryCategory'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit(24)
                ->get(),
            'posts' => BlogPost::query()
                ->with('featuredImage')
                ->where('status', 'published')
                ->latest('published_at')
                ->limit(3)
                ->get(),
            // The catalog in the page's JSON-LD (Organization «hasOfferCatalog»).
            'catalogCategories' => ProductCategory::navigation(),
        ]);
    }

    /**
     * Only types whose product is live, so the strip never links to a 404.
     *
     * @return array<int, array{name: string, slug: string, image: string}>
     */
    private function brushTypes(): array
    {
        $slugsBySku = Product::query()
            ->where('is_active', true)
            ->whereIn('sku', array_column(self::BRUSH_TYPES, 'sku'))
            ->pluck('slug', 'sku');

        $types = [];

        foreach (self::BRUSH_TYPES as $type) {
            if (isset($slugsBySku[$type['sku']])) {
                $types[] = ['name' => $type['name'], 'slug' => $slugsBySku[$type['sku']], 'image' => $type['image']];
            }
        }

        return $types;
    }
}
