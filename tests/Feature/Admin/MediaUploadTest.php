<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload(): void
    {
        $file = UploadedFile::fake()->image('logo.jpg');

        $this->post(route('admin.media.store'), ['file' => $file])
            ->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_upload_an_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'editor']);

        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->actingAs($user)->post(route('admin.media.store'), [
            'file' => $file,
            'alt_text' => 'برس صنعتی',
        ]);

        $response->assertRedirect(route('admin.media.index'));

        $media = Media::first();
        $this->assertNotNull($media);
        $this->assertSame('برس صنعتی', $media->alt_text);
        $this->assertSame(800, $media->width);
        $this->assertSame(600, $media->height);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->create('malware.php', 10, 'application/x-php');

        $this->actingAs($user)
            ->post(route('admin.media.store'), ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Media::count());
    }

    public function test_upload_rejects_oversized_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->image('big.jpg')->size(6000); // > 5MB limit

        $this->actingAs($user)
            ->post(route('admin.media.store'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_staff_can_delete_media_and_file_is_removed_from_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin']);

        $path = UploadedFile::fake()->image('old.jpg')->storeAs('media/2026/10', 'old.jpg', 'public');
        $media = Media::create([
            'disk' => 'public',
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 1000,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_upload_from_the_media_picker_returns_the_new_image_as_json(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)
            ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('brush.png', 640, 480)])
            ->assertCreated()
            ->assertJsonPath('data.width', 640)
            ->assertJsonStructure(['data' => ['id', 'url', 'thumb', 'alt', 'name']]);
    }

    public function test_upload_from_the_media_picker_reports_validation_errors_as_json(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)
            ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_media_picker_lists_and_searches_the_library_as_json(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'editor']);
        $this->actingAs($user);

        foreach (['برس سیمی', 'برس نایلونی'] as $alt) {
            $this->post(route('admin.media.store'), ['file' => UploadedFile::fake()->image('x.jpg'), 'alt_text' => $alt]);
        }

        $this->getJson(route('admin.media.index'))
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('last_page', 1);

        $this->getJson(route('admin.media.index', ['q' => 'سیمی']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.alt', 'برس سیمی');
    }

    public function test_library_page_paginates_and_filters_unused_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'editor']);
        $this->actingAs($user);

        foreach (range(1, 37) as $number) {
            Media::create(['disk' => 'public', 'path' => "media/2026/11/{$number}.jpg", 'mime_type' => 'image/jpeg', 'size' => 1000]);
        }

        $this->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('rel="next"', false);

        $this->get(route('admin.media.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('1.jpg');

        $this->get(route('admin.media.index', ['usage' => 'unused']))
            ->assertOk()
            ->assertSee('استفاده‌نشده');
    }

    public function test_staff_can_edit_an_images_alt_text(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $media = Media::create(['disk' => 'public', 'path' => 'media/a.jpg', 'mime_type' => 'image/jpeg', 'size' => 1000]);

        $this->actingAs($user)
            ->patch(route('admin.media.update', $media), ['alt_text' => 'برس دیسکی'])
            ->assertRedirect(route('admin.media.index'));

        $this->assertSame('برس دیسکی', $media->fresh()->alt_text);
    }
}
