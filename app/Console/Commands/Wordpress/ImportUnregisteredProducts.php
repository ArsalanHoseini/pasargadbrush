<?php

namespace App\Console\Commands\Wordpress;

use App\Models\MigrationMapping;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoRedirect;
use App\Services\WordPressImport\MediaCopier;
use App\Support\WordPressContent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The owner's newest product articles (written 2026-09-21…26 on the old site)
 * were never registered with WPML, so the fa-filtered ProductImporter cannot see
 * them — same site-side glitch as برس اسب / برس اسپیندل (see ApplyOwnerPriceList).
 * Imported here by known post ID with their full article, photos and WordPress
 * slug (so the old /product/{slug}/ URL keeps its exact path), as quote products.
 * Their old URLs, which fell back to a category page, are repointed to the product.
 *
 * Also gives the two earlier hand-imported articles proper paragraphs.
 * Idempotent; dry run unless --commit.
 */
#[Signature('wp:import-unregistered-products {--commit : Actually write changes; default is a dry run}')]
#[Description('Import the owner\'s newest WordPress product articles that WPML never registered')]
class ImportUnregisteredProducts extends Command
{
    /**
     * WordPress post ID => primary category slug. The articles' own WordPress
     * categories were mostly new ones that were never migrated, so the closest
     * existing category is chosen here; any already-migrated WordPress category
     * is attached as well.
     *
     * @var array<int, string>
     */
    public const PRODUCTS = [
        30710 => 'برس-صنعتی',                 // برس سیلندری
        30749 => 'برس-صنعتی',                 // برس دیسکی
        30750 => 'برس-نواری-و-درزگير',        // برس نواری تخت
        30751 => 'برس-نواری-و-درزگير',        // برس رول سیلندری
        30753 => 'برس-صنعتی',                 // برس روتاری
        30762 => 'برس-نواری-و-درزگير',        // برس استریپ
        30783 => 'برس-هويجشويي-و-سورتينگ',    // فرچه سورتینگ
        30818 => 'برس-قاليشويی-و-نظافتی',     // فرچه قالیشویی
        30821 => 'برس-هويجشويي-و-سورتينگ',    // فرچه شستشو سیب زمینی
        30842 => 'برس-قاليشويی-و-نظافتی',     // انواع برس دیسکی
        30850 => 'برس-صنعتی',                 // انواع برس سیلندری
        30991 => 'برس-صنعتی',                 // دستگاه برس چمن مصنوعی
        31086 => 'برس-صنعتی',                 // فرچه و برس نساجی
    ];

    /**
     * WordPress's default «بدون دسته‌بندی» — never attached; it must not reappear in the menus.
     */
    private const UNCATEGORIZED_SLUG = 'بدون-دستهبندی';

    /**
     * Earlier hand-imported articles whose text was stored without paragraphs.
     *
     * @var array<int, string>
     */
    private const REFORMAT_SKUS = ['WP-30801', 'WP-30868'];

    public function handle(MediaCopier $mediaCopier): int
    {
        $commit = (bool) $this->option('commit');

        if (! $commit) {
            $this->info('[DRY RUN] — pass --commit to actually write changes.');
        }

        DB::transaction(function () use ($commit, $mediaCopier) {
            foreach (self::PRODUCTS as $wpId => $categorySlug) {
                $this->importProduct($wpId, $categorySlug, $commit, $mediaCopier);
            }

            foreach (self::REFORMAT_SKUS as $sku) {
                $product = Product::where('sku', $sku)->first();

                if ($product && $product->description) {
                    $this->line(($commit ? 'Reformatting' : '[DRY RUN] Would reformat')." «{$product->name}»");

                    if ($commit) {
                        $product->update(['description' => WordPressContent::toHtml($product->description)]);
                    }
                }
            }
        });

        if ($commit) {
            Cache::forget('nav.categories');
        }

        return self::SUCCESS;
    }

    private function importProduct(int $wpId, string $categorySlug, bool $commit, MediaCopier $mediaCopier): void
    {
        $sku = 'WP-'.$wpId;

        if (Product::where('sku', $sku)->exists()) {
            $this->line("Skipped (already imported): {$sku}");

            return;
        }

        $wordpress = DB::connection('wordpress');
        $wpProduct = $wordpress->table('posts')->where('ID', $wpId)->where('post_status', 'publish')->first();

        if (! $wpProduct) {
            $this->warn("WordPress post {$wpId} not found or not published — skipping.");

            return;
        }

        $slug = urldecode($wpProduct->post_name);
        $this->line(($commit ? 'Importing' : '[DRY RUN] Would import')." «{$wpProduct->post_title}» (WP #{$wpId}, /products/{$slug})");

        if (! $commit) {
            return;
        }

        $primaryCategory = ProductCategory::where('slug', $categorySlug)->firstOrFail();

        $product = Product::create([
            'sku' => $sku,
            'slug' => $slug,
            'name' => $wpProduct->post_title,
            'primary_category_id' => $primaryCategory->id,
            'type' => 'quote',
            'description' => $wpProduct->post_content ? WordPressContent::toHtml($wpProduct->post_content) : null,
            'is_active' => true,
            'published_at' => $wpProduct->post_date,
        ]);

        $migratedCategoryIds = MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'product_cat')
            ->whereIn('source_id', $wordpress->table('term_relationships as tr')
                ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
                ->where('tr.object_id', $wpId)
                ->where('tt.taxonomy', 'product_cat')
                ->pluck('tt.term_taxonomy_id'))
            ->whereNotNull('target_id')
            ->pluck('target_id');

        $uncategorizedId = ProductCategory::where('slug', self::UNCATEGORIZED_SLUG)->value('id');

        $product->categories()->sync($migratedCategoryIds
            ->reject(fn (int $categoryId) => $categoryId === $uncategorizedId)
            ->push($primaryCategory->id)
            ->unique()
            ->values());

        $thumbnailId = $wordpress->table('postmeta')->where('post_id', $wpId)->where('meta_key', '_thumbnail_id')->value('meta_value');
        $galleryIds = $wordpress->table('postmeta')->where('post_id', $wpId)->where('meta_key', '_product_image_gallery')->value('meta_value');
        $attachmentIds = array_values(array_unique(array_filter(array_merge([$thumbnailId], $galleryIds ? explode(',', $galleryIds) : []))));

        foreach ($attachmentIds as $position => $attachmentId) {
            $media = $mediaCopier->copy((int) $attachmentId, true, $product->name);

            if ($media) {
                $product->images()->attach($media->id, ['role' => 'gallery', 'sort_order' => $position]);
            }
        }

        MigrationMapping::updateOrCreate(
            ['source_system' => 'wordpress', 'source_type' => 'product', 'source_id' => $wpId],
            [
                'target_type' => Product::class,
                'target_id' => $product->id,
                'status' => 'imported',
                'source_url' => "https://pasargadbrush.com/product/{$slug}/",
                'target_url' => "/products/{$slug}",
            ],
        );

        SeoRedirect::updateOrCreate(
            ['from_path' => SeoRedirect::normalizePath("/product/{$slug}")],
            [
                'to_path' => "/products/{$slug}",
                'status_code' => 301,
                'source' => SeoRedirect::SOURCE_WORDPRESS,
                'notes' => "WP product #{$wpId}",
                'is_active' => true,
            ],
        );
    }
}
