<?php

namespace Tests\Feature\WordPressImport;

use App\Models\BlogPost;
use App\Models\Media;
use App\Models\MigrationMapping;
use App\Services\WordPressImport\BlogImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostBodyRepairTest extends TestCase
{
    use RefreshDatabase;
    use WithFakeWordPressDatabase;

    private string $uploads;

    private const BODY = '[caption id="attachment_1" align="aligncenter" width="1024"]<img class="wp-image-1" src="http://localhost/pasargadbrush/wp-content/uploads/2024/04/Picture2-1024x690.jpg" alt="استریپ براش" width="1024" height="690" /> استریپ براش[/caption]'
        ."\n<p>متن <img src=\"http://localhost/pasargadbrush/wp-content/uploads/2024/04/Picture2.jpg\" alt=\"\"></p>";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->uploads = storage_path('framework/testing/wp-uploads-'.uniqid());
        File::ensureDirectoryExists($this->uploads.'/2024/04');
        $image = imagecreatetruecolor(4, 3);
        imagejpeg($image, $this->uploads.'/2024/04/Picture2.jpg');
        config(['wordpress_import.uploads_path' => $this->uploads]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->uploads);
        parent::tearDown();
    }

    private function importedPost(): BlogPost
    {
        return BlogPost::create([
            'title' => 'استریپ براش چیست؟', 'slug' => 'strip', 'body' => self::BODY,
            'status' => 'published', 'published_at' => now()->subYear(),
        ]);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $post = $this->importedPost();

        $this->artisan('wp:repair-post-bodies')
            ->expectsOutputToContain('1 distinct image file(s) to copy')
            ->assertSuccessful();

        $this->assertSame(self::BODY, $post->refresh()->body);
        $this->assertSame(0, Media::count());
    }

    public function test_images_are_copied_once_and_captions_become_figures(): void
    {
        $post = $this->importedPost();
        $updatedAt = $post->updated_at->toIso8601String();

        $this->travel(1)->day();
        $this->artisan('wp:repair-post-bodies --commit')->assertSuccessful();

        $media = Media::sole();
        $body = $post->refresh()->body;

        // The resized copy and the original are the same file: copied once.
        $this->assertSame('media/wp-import/', substr($media->path, 0, 16));
        Storage::disk('public')->assertExists($media->path);
        $this->assertSame('استریپ براش', $media->alt_text);
        $this->assertStringNotContainsString('wp-content', $body);
        $this->assertStringNotContainsString('[caption', $body);
        $this->assertSame(2, substr_count($body, 'src="/storage/'.$media->path.'"'));
        $this->assertStringContainsString('<figcaption>استریپ براش</figcaption></figure>', $body);
        $this->assertSame($updatedAt, $post->updated_at->toIso8601String(), 'The text did not change, so neither does lastmod.');

        // Re-running finds nothing left to do and copies nothing again.
        $this->artisan('wp:repair-post-bodies --commit')->assertSuccessful();
        $this->assertSame(1, Media::count());
        $this->assertSame(1, MigrationMapping::where('source_type', 'upload')->count());
    }

    public function test_a_file_missing_from_the_uploads_folder_is_reported_and_left_alone(): void
    {
        File::delete($this->uploads.'/2024/04/Picture2.jpg');
        $post = $this->importedPost();

        $this->artisan('wp:repair-post-bodies --commit')
            ->expectsOutputToContain('Not in the uploads folder, left as is: 2024/04/Picture2-1024x690.jpg')
            ->assertSuccessful();

        $this->assertStringContainsString('http://localhost/pasargadbrush/wp-content/uploads/2024/04/Picture2.jpg', $post->refresh()->body);
        $this->assertStringContainsString('<figure>', $post->body);
    }

    public function test_the_blog_importer_repairs_bodies_on_import(): void
    {
        $this->setUpFakeWordPressDatabase();
        $this->wp('posts')->insert([
            'ID' => 300, 'post_title' => 'مقاله تست', 'post_name' => 'article-test',
            'post_content' => self::BODY, 'post_status' => 'publish', 'post_date' => '2024-02-01 00:00:00',
        ]);
        $this->wp('icl_translations')->insert(['element_id' => 300, 'element_type' => 'post_post', 'language_code' => 'fa']);

        app(BlogImporter::class)->run(commit: true);

        $body = BlogPost::sole()->body;
        $this->assertStringNotContainsString('wp-content', $body);
        $this->assertStringContainsString('<figure>', $body);
    }
}
