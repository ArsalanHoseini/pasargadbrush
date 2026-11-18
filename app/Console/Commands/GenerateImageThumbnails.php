<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Media\ImageThumbnailer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('media:thumbnails {--force : Regenerate copies that already exist}')]
#[Description('Pre-generate the WebP listing/gallery copies of every uploaded image (otherwise made on first view)')]
class GenerateImageThumbnails extends Command
{
    public function handle(ImageThumbnailer $thumbnailer): int
    {
        $made = 0;
        $skipped = 0;

        Media::query()->where('mime_type', 'like', 'image/%')->lazyById()->each(function (Media $media) use ($thumbnailer, &$made, &$skipped) {
            foreach (ImageThumbnailer::WIDTHS as $width) {
                $exists = Storage::disk($media->disk)->exists($thumbnailer->path($media, $width));

                if ($exists && ! $this->option('force')) {
                    $skipped++;

                    continue;
                }

                $thumbnailer->generate($media, $width) ? $made++ : $skipped++;
            }
        });

        $this->info("Generated: {$made}, skipped: {$skipped}");

        return self::SUCCESS;
    }
}
