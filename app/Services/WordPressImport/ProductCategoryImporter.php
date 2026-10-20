<?php

namespace App\Services\WordPressImport;

use App\Models\MigrationMapping;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

/**
 * Imports Persian ("fa") WooCommerce product categories from the local
 * WordPress copy into product_categories. Read-only against the
 * "wordpress" connection; idempotent via migration_mappings.
 */
class ProductCategoryImporter
{
    public function run(bool $commit): ImportResult
    {
        $result = new ImportResult;

        $terms = DB::connection('wordpress')
            ->table('terms as t')
            ->join('term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
            ->join('icl_translations as it', function ($join) {
                $join->on('it.element_id', '=', 'tt.term_taxonomy_id')
                    ->where('it.element_type', '=', 'tax_product_cat');
            })
            ->where('it.language_code', 'fa')
            ->select('t.term_id', 'tt.term_taxonomy_id', 't.name', 't.slug', 'tt.parent', 'tt.description')
            ->orderBy('tt.parent')
            ->get();

        foreach ($terms as $term) {
            $this->importTerm($term, $commit, $result);
        }

        return $result;
    }

    private function importTerm(object $term, bool $commit, ImportResult $result): void
    {
        $existing = MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'product_cat')
            ->where('source_id', $term->term_taxonomy_id)
            ->first();

        if ($existing && $existing->target_id) {
            $result->skipped("دسته «{$term->name}» قبلاً وارد شده بود.");

            return;
        }

        $name = $term->name;
        $slug = urldecode($term->slug);

        if (! $commit) {
            $result->imported("[پیش‌نمایش] دسته جدید: «{$name}» (slug: {$slug})");

            return;
        }

        $parentId = null;
        if ($term->parent) {
            $parentMapping = MigrationMapping::query()
                ->where('source_system', 'wordpress')
                ->where('source_type', 'product_cat')
                ->where('source_id', $term->parent)
                ->first();
            $parentId = $parentMapping?->target_id;
        }

        $category = ProductCategory::create([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'description' => $term->description ?: null,
            'is_active' => true,
        ]);

        MigrationMapping::updateOrCreate(
            ['source_system' => 'wordpress', 'source_type' => 'product_cat', 'source_id' => $term->term_taxonomy_id],
            ['target_type' => ProductCategory::class, 'target_id' => $category->id, 'status' => 'imported', 'source_url' => null],
        );

        $result->imported("دسته «{$name}» وارد شد.");
    }
}
