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

        $extension = pathinfo($sourceFile, PATHINFO_EXTENSION) ?: 'jpg';
        $filename = Str::random(40).'.'.$extension;
        $destinationPath = 'media/wp-import/'.$filename;

        Storage::disk('public')->put($destinationPath, file_get_contents($sourceFile));

        [$width, $height] = @getimagesize($sourceFile) ?: [null, null];

        $media = Media::create([
            'disk' => 'public',
            'path' => $destinationPath,
            'mime_type' => mime_content_type($sourceFile) ?: 'application/octet-stream',
            'size' => filesize($sourceFile),
            'width' => $width,
            'height' => $height,
            'alt_text' => $altText,
        ]);

        MigrationMapping::updateOrCreate(
            ['source_system' => 'wordpress', 'source_type' => 'attachment', 'source_id' => $wpAttachmentId],
            ['target_type' => Media::class, 'target_id' => $media->id, 'status' => 'imported'],
        );

        return $media;
    }
}
