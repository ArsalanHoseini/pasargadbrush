<?php

namespace App\Services\WordPressImport;

use App\Models\Media;
use App\Models\MigrationMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies a WordPress attachment's real file into our media library.
 * Idempotent: re-running with the same attachment id reuses the existing Media row.
 */
class MediaCopier
{
    public function copy(int $wpAttachmentId, bool $commit, ?string $altText = null): ?Media
    {
        $existing = MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'attachment')
            ->where('source_id', $wpAttachmentId)
            ->first();

        if ($existing && $existing->target_id) {
            return Media::find($existing->target_id);
        }

        $relativePath = DB::connection('wordpress')
            ->table('postmeta')
            ->where('post_id', $wpAttachmentId)
            ->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        if (! $relativePath) {
            return null;
        }

        $uploadsPath = config('wordpress_import.uploads_path');
        $sourceFile = $uploadsPath ? rtrim($uploadsPath, '\\/').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath) : null;

        if (! $commit) {
            // Dry run: don't touch the filesystem or database, just report what would happen.
            return null;
        }

        if (! $sourceFile || ! is_file($sourceFile)) {
            return null;
        }

        $media = $this->store($sourceFile, $altText);

        MigrationMapping::updateOrCreate(
            ['source_system' => 'wordpress', 'source_type' => 'attachment', 'source_id' => $wpAttachmentId],
            ['target_type' => Media::class, 'target_id' => $media->id, 'status' => 'imported'],
        );

        return $media;
    }

    /**
     * Copies a file from the WordPress uploads folder by its path there
     * («2024/04/Picture1.jpg») — for images used inside post bodies, which
     * often have no attachment row. Idempotent per path: the mapping row
     * («upload», crc32 of the path, the path itself as source_url) makes a
     * second call return the same Media.
     */
    public function copyUploadFile(string $uploadsPath, string $relativePath, bool $commit, ?string $altText = null): ?Media
    {
        $relativePath = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $relativePath), '/');
        $sourceId = (int) sprintf('%u', crc32($relativePath));

        $existing = MigrationMapping::query()
            ->where('source_system', 'wordpress')
            ->where('source_type', 'upload')
            ->where('source_id', $sourceId)
            ->where('source_url', $relativePath)
            ->value('target_id');

        if ($existing && ($media = Media::find($existing))) {
            return $media;
        }

        $sourceFile = rtrim($uploadsPath, '\/').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (! $commit || ! is_file($sourceFile)) {
            return null;
        }

        $media = $this->store($sourceFile, $altText);

        MigrationMapping::updateOrCreate(
            ['source_system' => 'wordpress', 'source_type' => 'upload', 'source_id' => $sourceId],
            ['target_type' => Media::class, 'target_id' => $media->id, 'status' => 'imported', 'source_url' => $relativePath],
        );

        return $media;
    }

    private function store(string $sourceFile, ?string $altText): Media
    {
        $extension = pathinfo($sourceFile, PATHINFO_EXTENSION) ?: 'jpg';
        $destinationPath = 'media/wp-import/'.Str::random(40).'.'.strtolower($extension);

        Storage::disk('public')->put($destinationPath, file_get_contents($sourceFile));

        [$width, $height] = @getimagesize($sourceFile) ?: [null, null];

        return Media::create([
            'disk' => 'public',
            'path' => $destinationPath,
            'mime_type' => mime_content_type($sourceFile) ?: 'application/octet-stream',
            'size' => filesize($sourceFile),
            'width' => $width,
            'height' => $height,
            'alt_text' => $altText,
        ]);
    }
}
