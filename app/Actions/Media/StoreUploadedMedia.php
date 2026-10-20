<?php

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreUploadedMedia
{
    /**
     * Store an uploaded image on the public disk under a generated, unguessable
     * filename (never the original filename) and record it in the media library.
     */
    public function handle(UploadedFile $file, ?int $uploadedBy = null, ?string $altText = null): Media
    {
        $extension = $file->extension();
        $filename = Str::random(40).'.'.$extension;
        $path = $file->storeAs('media/'.date('Y/m'), $filename, 'public');

        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        return Media::create([
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt_text' => $altText,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
}
