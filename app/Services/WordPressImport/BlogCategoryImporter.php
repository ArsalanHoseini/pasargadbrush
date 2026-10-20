<?php

namespace App\Services\WordPressImport;

use App\Models\BlogCategory;
use App\Models\MigrationMapping;
use Illuminate\Support\Facades\DB;

/**
 * Imports Persian ("fa") blog categories ("category" taxonomy) from the
 * local WordPress copy. Read-only against the "wordpress" connection;
 * idempotent via migration_mappings.
 */
class BlogCategoryImporter
{
    public function run(bool $commit): ImportResult
    {
        $result = new ImportResult;

        $terms = DB::connection('wordpress')
            ->table('terms as t')
            ->join('term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
            ->join('icl_translations as it', function ($join) {
                $join->on('it.element_id', '=', 'tt.term_taxonomy_id')
                    ->where('it.element_type', '=', 'tax_category');
            })
            ->where('it.language_code', 'fa')
            ->select('tt.term_taxonomy_id', 't.name', 't.slug', 'tt.description')
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
            ->where('source_type', 'category')
            ->where('source_id', $term->term_taxonomy_id)
            ->first();

        if ($existing && $existing->target_id) {
            $result->skipped("دسته بلاگ «{$term->name}» قبلاً وارد شده بود.");

            return;
        }

        $name = $term->name;
        $slug = urldecode($term->slug);

        if (! $commit) {
            $result->imported("[پیش‌نمایش] دسته بلاگ جدید: «{$name}» (slug: {$slug})");

            return;
        }

        $category = BlogCategory::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $term->description ?: null,
        ]);

        MigrationMapping::updateOrCreate(
            ['source_system' => 'wordpress', 'source_type' => 'category', 'source_id' => $term->term_taxonomy_id],
            ['target_type' => BlogCategory::class, 'target_id' => $category->id, 'status' => 'imported'],
        );

        $result->imported("دسته بلاگ «{$name}» وارد شد.");
    }
}
