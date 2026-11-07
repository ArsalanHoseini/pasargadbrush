<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Small WebP copies of uploaded images for listings, so a 200px product card
 * doesn't download a 1200px original (PageSpeed "improve image delivery").
 * Uses PHP's built-in GD — no package. Copies are made once (on first use or
 * by `php artisan media:thumbnails`) and kept next to the originals; anything
 * GD can't read simply falls back to the original file.
 */
class ImageThumbnailer
{
    /**
     * Widths the views ask for: product/post cards, and the product page gallery.
     *
     * @var array<int, int>
     */
    public const WIDTHS = [480, 960];

    private const QUALITY = 80;

    public function url(Media $media, int $width): string
    {
        $path = $this->path($media, $width);
        $disk = Storage::disk($media->disk);

        if ($disk->exists($path) || $this->generate($media, $width)) {
            return $disk->url($path);
        }

        return $media->url();
    }

    public function path(Media $media, int $width): string
    {
        return 'thumbnails/'.$width.'/'.md5($media->path).'.webp';
    }

    public function generate(Media $media, int $width): bool
    {
        if (! function_exists('imagewebp') || ! in_array($media->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return false;
        }

        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            return false;
        }

        $source = @imagecreatefromstring($disk->get($media->path));

        if ($source === false) {
            return false;
        }

        imagepalettetotruecolor($source);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = min($width, $sourceWidth);
        $targetHeight = max(1, (int) round($sourceHeight * $targetWidth / $sourceWidth));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        ob_start();
        imagewebp($target, null, self::QUALITY);
        $webp = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        return $webp !== '' && $disk->put($this->path($media, $width), $webp);
    }
}
