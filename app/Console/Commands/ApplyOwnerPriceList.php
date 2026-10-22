<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Services\WordPressImport\MediaCopier;
use App\Support\PersianSlug;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Applies the real price list the owner provided (docs/لیست قیمت(1).pdf,
 * received 2026-10-22 — a genuine company price sheet, not WooCommerce data;
 * every product in the live catalog has zero price/SKU/variation set, see
 * docs/WORDPRESS_MIGRATION.md). Mapping and every judgment call here were
 * confirmed with the owner before this command was written — see
 * docs/DECISIONS.md for the full reasoning.
 *
 * Idempotent: re-running updates existing variants by SKU rather than
 * duplicating them, and skips products/categories that already exist.
 */
#[Signature('wp:apply-owner-price-list {--commit : Actually write changes; default is a dry run}')]
#[Description('Convert the matching products to type=priced using the real price list the owner provided')]
class ApplyOwnerPriceList extends Command
{
    /**
     * Each row: product SKU => array of [label, price in Toman].
     * Price source values are exactly as printed in the PDF (Toman); stored
     * as Rial (×10) per docs/DATABASE_DESIGN.md's currency convention.
     *
     * @var array<string, array<int, array{0: string, 1: int}>>
     */
    private const PRICE_MAP = [
        'WP-25055' => [['۷۰×۷۰×۷۰', 18_000_000]],       // برس چمن مصنوعی
        'WP-25145' => [['طول ۱ متر', 590_000]],          // برس درزگیر
        'WP-25085' => [['سفارشی', 500_000]],              // برس دستگاه واکس برقی
        'WP-25119' => [['قطر ۱۵۰ (سر دریلی)', 2_500_000]], // برس سنباده چوب
        'WP-25115' => [['استاندارد', 2_500_000]],         // برس طناب پاک کن
        'WP-25077' => [                                    // برس قالیشویی — two real sizes, owner-confirmed
            ['سیلندری ۹۰ سانتیمتر', 22_000_000],
            ['قطر ۳۸۰ میلیمتر', 2_950_000],
        ],
        'WP-30801' => [['قطر ۱۲۰', 800_000]],              // برس اسپیندل
        'WP-30868' => [['۱۱×۲۲', 2_400_000]],              // برس اسب
        'WP-25110' => [['قطر ۶۰ میلیمتر', 195_000]],      // برس مبل شو (kept copy — see below)
    ];

    /**
     * Owner confirmed these are near-duplicate content (same description,
     * "-2" slug suffix) — deactivate rather than delete, keep WP-25110 as
     * the priced one.
     */
    private const DEACTIVATE_SKUS = ['WP-25072'];

    /**
     * Real recent products (Sept 2026) that exist on the live site with full
     * content/images but were never registered in WPML's translation table
     * (element_id has no row at all in wp_icl_translations for either the
     * post or the product_cat terms it's tagged with — a site-side glitch,
     * not something we can fix) — so the normal fa-filtered WP importer
     * structurally cannot see them. Imported here directly by known post ID
     * instead, reusing the same MediaCopier used by the real importer.
     *
     * @var array<int, array{wp_id: int, sku: string, category_slug: string}>
     */
    private const UNREGISTERED_PRODUCTS = [
        ['wp_id' => 30801, 'sku' => 'WP-30801', 'category_slug' => 'برس-ابزاری'], // برس اسپیندل
        ['wp_id' => 30868, 'sku' => 'WP-30868', 'category_slug' => 'برس-قاليشويی-و-نظافتی'], // برس اسب
    ];

    public function handle(MediaCopier $mediaCopier): int
    {
        $commit = $this->option('commit');

        if (! $commit) {
            $this->info('[DRY RUN] — pass --commit to actually write changes.');
        }

        DB::transaction(function () use ($commit, $mediaCopier) {
            foreach (self::UNREGISTERED_PRODUCTS as $data) {
                $this->importUnregisteredProduct($data, $commit, $mediaCopier);
            }

            foreach (self::PRICE_MAP as $sku => $variants) {
                $this->applyPriceToProduct($sku, $variants, $commit);
            }

            foreach (self::DEACTIVATE_SKUS as $sku) {
                $this->deactivateDuplicate($sku, $commit);
            }

            if (! $commit) {
                DB::rollBack();
            }
        });

        return self::SUCCESS;
    }

    /** @param array{wp_id: int, sku: string, category_slug: string} $data */
    private function importUnregisteredProduct(array $data, bool $commit, MediaCopier $mediaCopier): void
    {
        if (Product::where('sku', $data['sku'])->exists()) {
            $this->line("Skipped (already imported): {$data['sku']}");

            return;
        }

        $wpProduct = DB::connection('wordpress')->table('posts')->where('ID', $data['wp_id'])->first();

        if (! $wpProduct) {
            $this->warn("WordPress post {$data['wp_id']} not found — skipping.");

            return;
        }

        $this->line(($commit ? 'Importing' : '[DRY RUN] Would import')." real product «{$wpProduct->post_title}» (WP #{$data['wp_id']})");

        if (! $commit) {
            return;
        }

        $category = ProductCategory::where('slug', $data['category_slug'])->first();

        $product = Product::create([
            'sku' => $data['sku'],
            'slug' => PersianSlug::make($wpProduct->post_title),
            'name' => $wpProduct->post_title,
            'primary_category_id' => $category?->id,
            'type' => 'priced',
            'description' => $wpProduct->post_content ?: null,
            'is_active' => true,
            'published_at' => $wpProduct->post_date,
        ]);

        $thumbnailId = DB::connection('wordpress')->table('postmeta')
            ->where('post_id', $data['wp_id'])->where('meta_key', '_thumbnail_id')->value('meta_value');
        $galleryIds = DB::connection('wordpress')->table('postmeta')
            ->where('post_id', $data['wp_id'])->where('meta_key', '_product_image_gallery')->value('meta_value');

        $attachmentIds = array_filter(array_merge([$thumbnailId], $galleryIds ? explode(',', $galleryIds) : []));

        foreach (array_values($attachmentIds) as $position => $attachmentId) {
            $media = $mediaCopier->copy((int) $attachmentId, true, $product->name);
            if ($media) {
                $product->images()->attach($media->id, ['role' => 'gallery', 'sort_order' => $position]);
            }
        }
    }

    /** @param array<int, array{0: string, 1: int}> $variants */
    private function applyPriceToProduct(string $sku, array $variants, bool $commit): void
    {
        $product = Product::where('sku', $sku)->first();

        if (! $product) {
            $this->warn("Skipped: no product with SKU {$sku}");

            return;
        }

        $this->line(($commit ? 'Updating' : '[DRY RUN] Would update')." «{$product->name}» ({$sku}) → type=priced, ".count($variants).' variant(s)');

        if (! $commit) {
            return;
        }

        $product->update(['type' => 'priced']);

        foreach ($variants as $position => [$label, $priceToman]) {
            ProductVariant::updateOrCreate(
                ['sku' => $sku.'-V'.($position + 1)],
                [
                    'product_id' => $product->id,
                    'label' => $label,
                    'price_rial' => $priceToman * 10,
                    'is_default' => $position === 0,
                    'sort_order' => $position,
                ],
            );
        }
    }

    private function deactivateDuplicate(string $sku, bool $commit): void
    {
        $product = Product::where('sku', $sku)->first();

        if (! $product) {
            return;
        }

        $this->line(($commit ? 'Deactivating' : '[DRY RUN] Would deactivate')." duplicate «{$product->name}» ({$sku})");

        if ($commit) {
            $product->update(['is_active' => false]);
        }
    }
}
