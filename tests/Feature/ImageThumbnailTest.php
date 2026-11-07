<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Services\Media\ImageThumbnailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageThumbnailTest extends TestCase
{
    use RefreshDatabase;

    private function storeImage(string $path, int $width, int $height): Media
    {
        $canvas = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($canvas);
        Storage::disk('public')->put($path, (string) ob_get_clean());

        return Media::create(['disk' => 'public', 'path' => $path, 'mime_type' => 'image/png', 'size' => 1]);
    }

    public function test_large_image_gets_a_smaller_webp_copy(): void
    {
        Storage::fake('public');
        $media = $this->storeImage('media/big.png', 1200, 800);

        $url = $media->thumbnailUrl(480);

        $path = app(ImageThumbnailer::class)->path($media, 480);
        $this->assertSame(Storage::disk('public')->url($path), $url);
        [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([480, 320, IMAGETYPE_WEBP], [$width, $height, $type]);
    }

    public function test_missing_or_non_image_files_fall_back_to_the_original(): void
    {
        Storage::fake('public');
        $missing = Media::create(['disk' => 'public', 'path' => 'media/missing.jpg', 'mime_type' => 'image/jpeg', 'size' => 1]);
        Storage::disk('public')->put('media/doc.pdf', '%PDF');
        $pdf = Media::create(['disk' => 'public', 'path' => 'media/doc.pdf', 'mime_type' => 'application/pdf', 'size' => 1]);

        $this->assertSame($missing->url(), $missing->thumbnailUrl(480));
        $this->assertSame($pdf->url(), $pdf->thumbnailUrl(480));
    }

    public function test_command_pre_generates_every_width(): void
    {
        Storage::fake('public');
        $media = $this->storeImage('media/small.png', 100, 100);

        $this->artisan('media:thumbnails')->assertSuccessful();

        foreach (ImageThumbnailer::WIDTHS as $width) {
            Storage::disk('public')->assertExists(app(ImageThumbnailer::class)->path($media, $width));
        }
    }
}
