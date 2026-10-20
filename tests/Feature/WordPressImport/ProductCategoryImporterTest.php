<?php

namespace Tests\Feature\WordPressImport;

use App\Models\MigrationMapping;
use App\Models\ProductCategory;
use App\Services\WordPressImport\ProductCategoryImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryImporterTest extends TestCase
{
    use RefreshDatabase;
    use WithFakeWordPressDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeWordPressDatabase();
    }

    private function seedCategoryTerm(int $termId, int $termTaxonomyId, string $name, string $slug, int $parent = 0): void
    {
        $this->wp('terms')->insert(['term_id' => $termId, 'name' => $name, 'slug' => $slug]);
        $this->wp('term_taxonomy')->insert([
            'term_taxonomy_id' => $termTaxonomyId,
            'term_id' => $termId,
            'taxonomy' => 'product_cat',
            'parent' => $parent,
        ]);
        $this->wp('icl_translations')->insert([
            'element_id' => $termTaxonomyId,
            'element_type' => 'tax_product_cat',
            'language_code' => 'fa',
        ]);
    }

    public function test_dry_run_reports_without_writing_anything(): void
    {
        $this->seedCategoryTerm(1, 10, 'برس صنعتی', 'brush-sanati');

        $result = (new ProductCategoryImporter)->run(commit: false);

        $this->assertSame(1, $result->imported);
        $this->assertSame(0, ProductCategory::count());
        $this->assertSame(0, MigrationMapping::count());
    }

    public function test_commit_creates_category_and_mapping(): void
    {
        $this->seedCategoryTerm(1, 10, 'برس صنعتی', 'brush-sanati');

        $result = (new ProductCategoryImporter)->run(commit: true);

        $this->assertSame(1, $result->imported);
        $category = ProductCategory::first();
        $this->assertSame('برس صنعتی', $category->name);
        $this->assertSame('brush-sanati', $category->slug);

        $this->assertDatabaseHas('migration_mappings', [
            'source_system' => 'wordpress',
            'source_type' => 'product_cat',
            'source_id' => 10,
            'target_id' => $category->id,
        ]);
    }

    public function test_rerunning_is_idempotent(): void
    {
        $this->seedCategoryTerm(1, 10, 'برس صنعتی', 'brush-sanati');

        (new ProductCategoryImporter)->run(commit: true);
        $result = (new ProductCategoryImporter)->run(commit: true);

        $this->assertSame(0, $result->imported);
        $this->assertSame(1, $result->skipped);
        $this->assertSame(1, ProductCategory::count());
    }

    public function test_child_category_resolves_parent_from_mapping(): void
    {
        $this->seedCategoryTerm(1, 10, 'برس صنعتی', 'brush-sanati');
        $this->seedCategoryTerm(2, 20, 'برس نواری', 'brush-navari', parent: 10);

        (new ProductCategoryImporter)->run(commit: true);

        $parent = ProductCategory::where('slug', 'brush-sanati')->first();
        $child = ProductCategory::where('slug', 'brush-navari')->first();

        $this->assertSame($parent->id, $child->parent_id);
    }

    public function test_non_persian_translations_are_ignored(): void
    {
        $this->wp('terms')->insert(['term_id' => 1, 'name' => 'Industrial Brush', 'slug' => 'industrial-brush']);
        $this->wp('term_taxonomy')->insert(['term_taxonomy_id' => 10, 'term_id' => 1, 'taxonomy' => 'product_cat']);
        $this->wp('icl_translations')->insert(['element_id' => 10, 'element_type' => 'tax_product_cat', 'language_code' => 'en']);

        $result = (new ProductCategoryImporter)->run(commit: true);

        $this->assertSame(0, $result->total());
        $this->assertSame(0, ProductCategory::count());
    }
}
