<?php

namespace App\Services\WordPressImport;

use App\Models\MigrationMapping;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Imports published Persian ("fa") WooCommerce products from the local
 * WordPress copy. Every product found in the source has no price/SKU/
 * variations set (verified before writing this importer — see
 * docs/WORDPRESS_MIGRATION.md) so all imported products land as
 * type = "quote". Read-only against the "wordpress" connection;
 * idempotent via migration_mappings. Requires product categories to
 * have been imported first (ProductCategoryImporter).
 */
class ProductImporter
{
    public function __construct(private MediaCopier $mediaCopier) {}

    public function run(bool $commit): ImportResult
    {
        $result = new ImportResult;

        $products = DB::connection('wordpress')
            ->table('posts as p')
            ->join('icl_translations as it', function ($join) {
                $join->on('it.element_id', '=', 'p.ID')
                    ->where('it.element_type', '=', 'post_product');
            })
            ->where('it.language_code', 'fa')
            ->where('p.post_status', 'publish')
            ->select('p.ID', 'p.post_title', 'p.post_name', 'p.post_excerpt', 'p.post_date')
            ->get();

        foreach ($products as $product) {
            $this->importProduct($product, $commit, $result);
        }

        return $result;
    }

    private function importProduct(object $wpProduct, bool $commit, ImportResult $result): void
    {
        $existing = MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'product')
            ->where('source_id', $wpProduct->ID)
            ->first();

        if ($existing && $existing->target_id) {
            $result->skipped("محصول «{$wpProduct->post_title}» قبلاً وارد شده بود.");

            return;
        }

        $slug = urldecode($wpProduct->post_name);
        $categoryTermTaxonomyIds = $this->categoryTermTaxonomyIds($wpProduct->ID);
        $categoryIds = $this->mapToLocalCategoryIds($categoryTermTaxonomyIds);

        if (! $commit) {
            $result->imported("[پیش‌نمایش] محصول: «{$wpProduct->post_title}» (slug: {$slug}, {$categoryIds->count()} دسته)");

            return;
        }

        $product = Product::create([
            'sku' => 'WP-'.$wpProduct->ID,
            'slug' => $slug,
            'name' => $wpProduct->post_title,
            'primary_category_id' => $categoryIds->first(),
            'type' => 'quote',
            'description' => $wpProduct->post_excerpt ?: null,
            'is_active' => true,
            'published_at' => $wpProduct->post_date,
        ]);

        if ($categoryIds->isNotEmpty()) {
            $product->categories()->sync($categoryIds);
        }

        $this->attachImages($product, $wpProduct->ID, $commit);

        MigrationMapping::updateOrCreate(
            ['source_system' => 'wordpress', 'source_type' => 'product', 'source_id' => $wpProduct->ID],
            [
                'target_type' => Product::class,
                'target_id' => $product->id,
                'status' => 'imported',
                'source_url' => "https://pasargadbrush.com/product/{$slug}/",
                'target_url' => "/products/{$slug}",
            ],
        );

        $result->imported("محصول «{$wpProduct->post_title}» وارد شد.");
    }

    /** @return Collection<int, int> */
    private function categoryTermTaxonomyIds(int $wpPostId): Collection
    {
        return DB::connection('wordpress')
            ->table('term_relationships as tr')
            ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $wpPostId)
            ->where('tt.taxonomy', 'product_cat')
            ->pluck('tt.term_taxonomy_id');
    }

    /** @return Collection<int, int> */
    private function mapToLocalCategoryIds(Collection $termTaxonomyIds): Collection
    {
        if ($termTaxonomyIds->isEmpty()) {
            return collect();
        }

        return MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'product_cat')
            ->whereIn('source_id', $termTaxonomyIds)
            ->whereNotNull('target_id')
            ->pluck('target_id');
    }

    private function attachImages(Product $product, int $wpPostId, bool $commit): void
    {
        $thumbnailId = DB::connection('wordpress')->table('postmeta')
            ->where('post_id', $wpPostId)->where('meta_key', '_thumbnail_id')
            ->value('meta_value');

        $galleryIds = DB::connection('wordpress')->table('postmeta')
            ->where('post_id', $wpPostId)->where('meta_key', '_product_image_gallery')
            ->value('meta_value');

        $attachmentIds = array_filter(array_merge(
            [$thumbnailId],
            $galleryIds ? explode(',', $galleryIds) : [],
        ));

        foreach (array_values($attachmentIds) as $position => $attachmentId) {
            $media = $this->mediaCopier->copy((int) $attachmentId, $commit, $product->name);

            if ($media) {
                $product->images()->attach($media->id, [
                    'role' => 'gallery',
                    'sort_order' => $position,
                ]);
            }
        }
    }
}
