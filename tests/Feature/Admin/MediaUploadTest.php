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
}
