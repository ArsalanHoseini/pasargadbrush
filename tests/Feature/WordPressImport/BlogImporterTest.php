<?php

namespace Tests\Feature\WordPressImport;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\MigrationMapping;
use App\Services\WordPressImport\BlogCategoryImporter;
use App\Services\WordPressImport\BlogImporter;
use App\Services\WordPressImport\MediaCopier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogImporterTest extends TestCase
{
    use RefreshDatabase;
    use WithFakeWordPressDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeWordPressDatabase();
        Storage::fake('public');
    }

    public function test_blog_category_import_is_idempotent(): void
    {
        $this->wp('terms')->insert(['term_id' => 1, 'name' => 'اخبار', 'slug' => 'news']);
        $this->wp('term_taxonomy')->insert(['term_taxonomy_id' => 10, 'term_id' => 1, 'taxonomy' => 'category']);
        $this->wp('icl_translations')->insert(['element_id' => 10, 'element_type' => 'tax_category', 'language_code' => 'fa']);

        (new BlogCategoryImporter)->run(commit: true);
        $second = (new BlogCategoryImporter)->run(commit: true);

        $this->assertSame(1, BlogCategory::count());
        $this->assertSame(1, $second->skipped);
    }

    public function test_published_fa_post_is_imported_with_category_and_body(): void
    {
        $category = BlogCategory::create(['name' => 'اخبار', 'slug' => 'news']);
        MigrationMapping::create([
            'source_system' => 'wordpress', 'source_type' => 'category', 'source_id' => 10,
            'target_type' => BlogCategory::class, 'target_id' => $category->id, 'status' => 'imported',
        ]);

        $this->wp('posts')->insert([
            'ID' => 300, 'post_title' => 'مقاله تست', 'post_name' => 'article-test',
            'post_content' => 'متن کامل مقاله', 'post_status' => 'publish', 'post_date' => '2024-02-01 00:00:00',
        ]);
        $this->wp('icl_translations')->insert(['element_id' => 300, 'element_type' => 'post_post', 'language_code' => 'fa']);
        $this->wp('term_relationships')->insert(['object_id' => 300, 'term_taxonomy_id' => 10]);
        $this->wp('term_taxonomy')->insert(['term_taxonomy_id' => 10, 'term_id' => 1, 'taxonomy' => 'category']);

        $result = (new BlogImporter(app(MediaCopier::class)))->run(commit: true);

        $this->assertSame(1, $result->imported);
        $post = BlogPost::first();
        $this->assertSame('مقاله تست', $post->title);
        $this->assertSame('article-test', $post->slug);
        $this->assertSame('متن کامل مقاله', $post->body);
        $this->assertSame('published', $post->status);
        $this->assertSame($category->id, $post->blog_category_id);
    }

    public function test_draft_posts_are_never_imported(): void
    {
        $this->wp('posts')->insert([
            'ID' => 301, 'post_title' => 'پیش‌نویس', 'post_name' => 'draft-post',
            'post_content' => 'متن', 'post_status' => 'draft', 'post_date' => '2024-02-01 00:00:00',
        ]);
        $this->wp('icl_translations')->insert(['element_id' => 301, 'element_type' => 'post_post', 'language_code' => 'fa']);

        $result = (new BlogImporter(app(MediaCopier::class)))->run(commit: true);

        $this->assertSame(0, $result->total());
        $this->assertSame(0, BlogPost::count());
    }
}
