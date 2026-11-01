<?php

namespace App\Services\WordPressImport;

use App\Models\BlogPost;
use App\Models\Media;
use App\Models\MigrationMapping;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoRedirect;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Works out, from the read-only local WordPress copy, where every public URL
 * of the old site should now point, so search rankings and old links carry
 * over. Old URL shapes (permalinks: /%postname%/, WooCommerce /product/ and
 * /product-category/, WPML language-as-parameter so ar/en items have their
 * own slugs on the same paths):
 *
 *   /product/{slug}            → /products/{slug}
 *   /product-category/{slug}   → /categories/{slug}, or the closest match
 *   /{post-slug}               → /blog/{slug}
 *   /{page-slug}               → the matching new page
 *   /wp-content/uploads/{file} → the copied media file
 *   ar/en translations         → their Persian original's new URL
 *
 * Yoast Premium's own redirects (incl. 410 Gone) are carried over last, as
 * the owner's explicit intent. Target URLs are always paths on this site.
 */
class LegacyRedirectBuilder
{
    /**
     * Old Persian page slug → new path. Pages without a new equivalent go to
     * the closest useful page; demo leftovers are gone (410 → null).
     */
    private const PAGE_TARGETS = [
        'home-2' => '/',
        'about-us-2' => '/page/about-us',
        'contact-us' => '/page/contact-us',
        'فرم-سفارش-آنلاین' => '/page/contact-us',
        'blog' => '/blog',
        'shop' => '/',
        'محصولات' => '/',
        'کاتالوگ-محصولات' => '/',
        'گالری-تصاویر' => '/',
        'services' => '/',
        'faq' => '/page/contact-us',
        'cart' => '/cart',
        'checkout' => '/checkout',
        'my-account' => '/account',
        'wishlist' => '/',
        'products-compare' => '/',
        'برگه-نمونه' => null,
    ];

    /** Yoast's sitemap index and sub-sitemaps on the old site. */
    private const OLD_SITEMAPS = [
        'sitemap_index.xml', 'post-sitemap.xml', 'page-sitemap.xml', 'portfolio-sitemap.xml', 'product-sitemap.xml',
        'category-sitemap.xml', 'portfolio_cat-sitemap.xml', 'portfolio_tags-sitemap.xml', 'product_cat-sitemap.xml',
        'product_tag-sitemap.xml', 'author-sitemap.xml',
    ];

    private ConnectionInterface $wp;

    /** @var array<string, array{to: ?string, code: int, note: string}> keyed by normalized from-path */
    private array $redirects = [];

    /**
     * @return array<string, array{to: ?string, code: int, note: string}>
     */
    public function build(): array
    {
        $this->wp = DB::connection('wordpress');
        $this->redirects = [];

        $this->products();
        $this->productCategories();
        $this->posts();
        $this->pages();
        $this->taxonomyArchives();
        $this->portfolio();
        $this->uploads();
        $this->staticUrls();
        $this->yoastRedirects();

        return $this->redirects;
    }

    private function products(): void
    {
        foreach ($this->publishedPosts('product') as $post) {
            $target = $this->productPath($post->ID)
                ?? $this->productPath($this->persianOriginal('post_product', $post->ID))
                ?? $this->productFallbackViaCategory($post->ID)
                // Not on the new site (e.g. added to the old site after the import): search for it.
                ?? $this->searchPath($post->post_title);

            $this->add('/product/'.$this->slug($post->post_name), $target, "WP product #{$post->ID}");
        }
    }

    private function productCategories(): void
    {
        foreach ($this->terms('product_cat') as $term) {
            $target = $this->categoryPath($term->term_taxonomy_id)
                ?? $this->categoryPath($this->persianOriginal('tax_product_cat', $term->term_taxonomy_id))
                ?? $this->closestPathForTerm($term->term_taxonomy_id)
                ?? $this->searchPath($term->name);

            $this->add('/product-category/'.$this->termPath($term, 'product_cat'), $target, "WP product_cat #{$term->term_id}");
        }
    }

    private function posts(): void
    {
        foreach ($this->publishedPosts('post') as $post) {
            $target = $this->postPath($post->ID)
                ?? $this->postPath($this->persianOriginal('post_post', $post->ID))
                ?? '/blog';

            $this->add('/'.$this->slug($post->post_name), $target, "WP post #{$post->ID}");
        }
    }

    private function pages(): void
    {
        foreach ($this->publishedPosts('page') as $page) {
            $persianId = $this->persianOriginal('post_page', $page->ID) ?? $page->ID;
            $persianSlug = $this->slug((string) $this->wp->table('posts')->where('ID', $persianId)->value('post_name'));

            $target = array_key_exists($persianSlug, self::PAGE_TARGETS) ? self::PAGE_TARGETS[$persianSlug] : '/';

            $this->add('/'.$this->slug($page->post_name), $target, "WP page #{$page->ID}", $target === null ? 410 : 301);
        }
    }

    /**
     * Blog categories/tags have no archive pages on the new site; product
     * tags point at their only product, or a search for the tag.
     */
    private function taxonomyArchives(): void
    {
        foreach ($this->terms('category') as $term) {
            $this->add('/category/'.$this->termPath($term, 'category'), '/blog', "WP category #{$term->term_id}");
        }

        foreach ($this->terms('post_tag') as $term) {
            $this->add('/tag/'.$this->slug($term->slug), '/blog', "WP post_tag #{$term->term_id}");
        }

        foreach ($this->terms('product_tag') as $term) {
            $productPaths = $this->productPathsForTerm($term->term_taxonomy_id);
            $target = count($productPaths) === 1 ? $productPaths[0] : $this->searchPath($term->name);

            $this->add('/product-tag/'.$this->slug($term->slug), $target, "WP product_tag #{$term->term_id}");
        }
    }

    /**
     * Theme demo "portfolio" items were never real content.
     */
    private function portfolio(): void
    {
        foreach ($this->publishedPosts('portfolio') as $post) {
            $this->add('/portfolio/'.$this->slug($post->post_name), '/', "WP portfolio #{$post->ID}");
        }
    }

    /**
     * Images indexed by search engines under /wp-content/uploads/. Resized
     * copies (-300x200) are matched by SeoRedirect::resolve() to the original.
     */
    private function uploads(): void
    {
        $mappings = MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'attachment')
            ->whereNotNull('target_id')
            ->get(['source_id', 'target_id']);

        $media = Media::query()->whereIn('id', $mappings->pluck('target_id'))->get()->keyBy('id');
        $files = $this->wp->table('postmeta')
            ->where('meta_key', '_wp_attached_file')
            ->whereIn('post_id', $mappings->pluck('source_id'))
            ->pluck('meta_value', 'post_id');

        foreach ($mappings as $mapping) {
            $file = $files[$mapping->source_id] ?? null;
            $target = $media->get($mapping->target_id);

            if ($file && $target) {
                $this->add('/wp-content/uploads/'.$file, '/storage/'.$target->path, "WP attachment #{$mapping->source_id}");
            }
        }
    }

    private function staticUrls(): void
    {
        foreach (self::OLD_SITEMAPS as $sitemap) {
            $this->add('/'.$sitemap, '/sitemap.xml', 'Old Yoast sitemap');
        }

        $this->add('/feed', '/blog/feed.xml', 'Old RSS feed');
    }

    private function yoastRedirects(): void
    {
        $serialized = $this->wp->table('options')->where('option_name', 'wpseo-premium-redirects-export-plain')->value('option_value');
        $redirects = $serialized ? @unserialize($serialized, ['allowed_classes' => false]) : [];

        foreach (is_array($redirects) ? $redirects : [] as $origin => $redirect) {
            $code = (int) ($redirect['type'] ?? 301);
            $url = trim((string) ($redirect['url'] ?? ''));
            $target = $url === '' ? null : SeoRedirect::normalizePath($url);

            $this->add('/'.$origin, $target, 'Yoast Premium redirect', $code === 410 || $target === null ? 410 : 301);
        }
    }

    private function add(string $from, ?string $to, string $note, int $code = 301): void
    {
        $from = SeoRedirect::normalizePath($from);

        if ($from === '/' || ($to !== null && SeoRedirect::normalizePath($to) === $from)) {
            return;
        }

        $this->redirects[$from] = ['to' => $to, 'code' => $code, 'note' => $note];
    }

    /**
     * The site search for a name, minus the generic «برس»/«فرچه» words so the
     * search matches on what distinguishes it (e.g. «سیلندری»).
     */
    private function searchPath(string $name): string
    {
        $terms = trim(preg_replace('/(^|\s)(برس|برس‌های|برسهای|فرچه|های|انواع)(?=\s|$)/u', ' ', str_replace('-', ' ', $name)) ?? $name);

        return '/search?q='.rawurlencode($terms !== '' ? $terms : $name);
    }

    private function productPath(?int $wpId): ?string
    {
        if ($wpId === null) {
            return null;
        }

        $product = Product::query()->where('sku', 'WP-'.$wpId)->first();

        return $product?->is_active ? '/products/'.$product->slug : null;
    }

    /**
     * A product that wasn't migrated (or was deactivated) goes to the new
     * page of one of its categories.
     */
    private function productFallbackViaCategory(int $wpId): ?string
    {
        $termIds = $this->wp->table('term_relationships as r')
            ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'r.term_taxonomy_id')
            ->where('r.object_id', $wpId)
            ->where('tt.taxonomy', 'product_cat')
            ->pluck('tt.term_taxonomy_id');

        foreach ($termIds as $termId) {
            if ($path = $this->categoryPath($termId)) {
                return $path;
            }
        }

        return null;
    }

    private function categoryPath(?int $termTaxonomyId): ?string
    {
        if ($termTaxonomyId === null) {
            return null;
        }

        $categoryId = $this->mappedTargetId('product_cat', $termTaxonomyId);
        $category = $categoryId ? ProductCategory::query()->find($categoryId) : null;

        return $category?->is_active ? '/categories/'.$category->slug : null;
    }

    /**
     * For a product category the new site doesn't have: a single-product
     * category goes to that product; otherwise to the new category most of
     * its products belong to.
     */
    private function closestPathForTerm(int $termTaxonomyId): ?string
    {
        $productPaths = $this->productPathsForTerm($termTaxonomyId);

        if (count($productPaths) === 1) {
            return $productPaths[0];
        }

        $categoryId = Product::query()
            ->whereIn('sku', array_map(fn ($id) => 'WP-'.$id, $this->objectIdsForTerm($termTaxonomyId)))
            ->whereNotNull('primary_category_id')
            ->selectRaw('primary_category_id, count(*) as products')
            ->groupBy('primary_category_id')
            ->orderByDesc('products')
            ->value('primary_category_id');

        $category = $categoryId ? ProductCategory::query()->find($categoryId) : null;

        return $category?->is_active ? '/categories/'.$category->slug : null;
    }

    /**
     * @return list<string>
     */
    private function productPathsForTerm(int $termTaxonomyId): array
    {
        return Product::query()
            ->whereIn('sku', array_map(fn ($id) => 'WP-'.$id, $this->objectIdsForTerm($termTaxonomyId)))
            ->where('is_active', true)
            ->pluck('slug')
            ->map(fn ($slug) => '/products/'.$slug)
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function objectIdsForTerm(int $termTaxonomyId): array
    {
        return $this->wp->table('term_relationships')->where('term_taxonomy_id', $termTaxonomyId)->pluck('object_id')->map(fn ($id) => (int) $id)->all();
    }

    private function postPath(?int $wpId): ?string
    {
        if ($wpId === null) {
            return null;
        }

        $postId = $this->mappedTargetId('post', $wpId);
        $post = $postId ? BlogPost::query()->find($postId) : null;

        return $post && $post->status === 'published' ? '/blog/'.$post->slug : null;
    }

    private function mappedTargetId(string $sourceType, int $sourceId): ?int
    {
        return MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->value('target_id');
    }

    /**
     * The Persian (default-language) element of a WPML translation group,
     * or null when the element is itself Persian / untranslated.
     */
    private function persianOriginal(string $elementType, int $elementId): ?int
    {
        $translation = $this->wp->table('icl_translations')
            ->where('element_type', $elementType)
            ->where('element_id', $elementId)
            ->first(['trid', 'language_code']);

        if (! $translation || $translation->language_code === 'fa') {
            return null;
        }

        $persianId = $this->wp->table('icl_translations')
            ->where('trid', $translation->trid)
            ->where('language_code', 'fa')
            ->value('element_id');

        return $persianId ? (int) $persianId : null;
    }

    /**
     * @return Collection<int, object{ID: int, post_name: string}>
     */
    private function publishedPosts(string $type)
    {
        return $this->wp->table('posts')
            ->where('post_type', $type)
            ->where('post_status', 'publish')
            ->where('post_name', '!=', '')
            ->get(['ID', 'post_name', 'post_title']);
    }

    /**
     * @return Collection<int, object>
     */
    private function terms(string $taxonomy)
    {
        return $this->wp->table('terms as t')
            ->join('term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
            ->where('tt.taxonomy', $taxonomy)
            ->get(['t.term_id', 't.slug', 't.name', 'tt.term_taxonomy_id', 'tt.parent']);
    }

    /**
     * Hierarchical term URLs include their parents: parent-slug/child-slug.
     */
    private function termPath(object $term, string $taxonomy): string
    {
        $segments = [$this->slug($term->slug)];
        $parentId = (int) $term->parent;
        $guard = 0;

        while ($parentId !== 0 && $guard++ < 10) {
            $parent = $this->wp->table('terms as t')
                ->join('term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
                ->where('tt.taxonomy', $taxonomy)
                ->where('t.term_id', $parentId)
                ->first(['t.slug', 'tt.parent']);

            if (! $parent) {
                break;
            }

            array_unshift($segments, $this->slug($parent->slug));
            $parentId = (int) $parent->parent;
        }

        return implode('/', $segments);
    }

    /**
     * WordPress stores non-Latin slugs percent-encoded.
     */
    private function slug(string $slug): string
    {
        return rawurldecode($slug);
    }
}
