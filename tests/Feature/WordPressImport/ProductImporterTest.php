<?php

namespace Tests\Feature\WordPressImport;

use App\Models\Media;
use App\Models\MigrationMapping;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\WordPressImport\MediaCopier;
use App\Services\WordPressImport\ProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImporterTest extends TestCase
{
    use RefreshDatabase;
    use WithFakeWordPressDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeWordPressDatabase();
        Storage::fake('public');
    }

    private function seedCategoryMapping(int $termTaxonomyId): ProductCategory
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'brush-sanati']);
        MigrationMapping::create([
            'source_system' => 'wordpress',
            'source_type' => 'product_cat',
            'source_id' => $termTaxonomyId,
            'target_type' => ProductCategory::class,
            'target_id' => $category->id,
            'status' => 'imported',
        ]);

        return $category;
    }

    private function seedProduct(int $postId, string $title, string $slug, ?int $categoryTermTaxonomyId = null): void
    {
        $this->wp('posts')->insert([
            'ID' => $postId,
            'post_title' => $title,
            'post_name' => $slug,
            'post_excerpt' => 'توضیحات محصول',
            'post_status' => 'publish',
            'post_date' => '2024-01-01 00:00:00',
        ]);
        $this->wp('icl_translations')->insert([
            'element_id' => $postId,
            'element_type' => 'post_product',
            'language_code' => 'fa',
        ]);

        if ($categoryTermTaxonomyId) {
            $this->wp('term_relationships')->insert([
                'object_id' => $postId,
                'term_taxonomy_id' => $categoryTermTaxonomyId,
            ]);
            $this->wp('term_taxonomy')->insert([
                'term_taxonomy_id' => $categoryTermTaxonomyId,
                'term_id' => $categoryTermTaxonomyId,
                'taxonomy' => 'product_cat',
            ]);
        }
    }

    public function test_dry_run_does_not_create_products(): void
    {
        $this->seedProduct(100, 'برس صنعتی تست', 'brush-test');

        $result = (new ProductImporter(app(MediaCopier::class)))->run(commit: false);

        $this->assertSame(1, $result->imported);
        $this->assertSame(0, Product::count());
    }

    public function test_commit_creates_quote_product_with_category(): void
    {
        $category = $this->seedCategoryMapping(10);
        $this->seedProduct(100, 'برس صنعتی تست', 'brush-test', categoryTermTaxonomyId: 10);

        (new ProductImporter(app(MediaCopier::class)))->run(commit: true);

        $product = Product::first();
        $this->assertNotNull($product);
        $this->assertSame('برس صنعتی تست', $product->name);
        $this->assertSame('brush-test', $product->slug);
        $this->assertSame('WP-100', $product->sku);
        $this->assertSame('quote', $product->type);
        $this->assertSame('توضیحات محصول', $product->description);
        $this->assertTrue($product->is_active);
        $this->assertSame($category->id, $product->primary_category_id);
        $this->assertTrue($product->categories->contains($category));
    }

    public function test_commit_copies_featured_image_into_media_library(): void
    {
        $this->seedProduct(100, 'برس صنعتی تست', 'brush-test');
        $this->wp('postmeta')->insert([
            'post_id' => 100, 'meta_key' => '_thumbnail_id', 'meta_value' => 500,
        ]);
        $this->wp('posts')->insert([
            'ID' => 500, 'post_title' => 'attachment', 'post_name' => 'attachment',
            'post_status' => 'inherit', 'post_date' => '2024-01-01 00:00:00',
        ]);

        $fixtureDir = sys_get_temp_dir().'/wp_fixture_uploads/2024/01';
        @mkdir($fixtureDir, 0777, true);
        $imagePath = $fixtureDir.'/sample.jpg';
        imagejpeg(imagecreatetruecolor(50, 40), $imagePath);

        $this->wp('postmeta')->insert([
            'post_id' => 500, 'meta_key' => '_wp_attached_file', 'meta_value' => '2024/01/sample.jpg',
        ]);
        config(['wordpress_import.uploads_path' => sys_get_temp_dir().'/wp_fixture_uploads']);

        (new ProductImporter(app(MediaCopier::class)))->run(commit: true);

        $product = Product::first();
        $this->assertSame(1, Media::count());
        $media = Media::first();
        $this->assertSame(50, $media->width);
        $this->assertSame(40, $media->height);
        Storage::disk('public')->assertExists($media->path);
        $this->assertTrue($product->images->contains($media));

        @unlink($imagePath);
    }

    public function test_rerunning_import_is_idempotent(): void
    {
        $this->seedProduct(100, 'برس صنعتی تست', 'brush-test');

        (new ProductImporter(app(MediaCopier::class)))->run(commit: true);
        $result = (new ProductImporter(app(MediaCopier::class)))->run(commit: true);

        $this->assertSame(0, $result->imported);
        $this->assertSame(1, $result->skipped);
        $this->assertSame(1, Product::count());
    }

    public function test_only_published_fa_products_are_imported(): void
    {
        // draft fa product
        $this->wp('posts')->insert([
            'ID' => 200, 'post_title' => 'Draft', 'post_name' => 'draft',
            'post_status' => 'draft', 'post_date' => '2024-01-01 00:00:00',
        ]);
        $this->wp('icl_translations')->insert(['element_id' => 200, 'element_type' => 'post_product', 'language_code' => 'fa']);

        // published english product (should be ignored)
        $this->wp('posts')->insert([
            'ID' => 201, 'post_title' => 'English', 'post_name' => 'english',
            'post_status' => 'publish', 'post_date' => '2024-01-01 00:00:00',
        ]);
        $this->wp('icl_translations')->insert(['element_id' => 201, 'element_type' => 'post_product', 'language_code' => 'en']);

        $result = (new ProductImporter(app(MediaCopier::class)))->run(commit: true);

        $this->assertSame(0, $result->total());
        $this->assertSame(0, Product::count());
    }
}
