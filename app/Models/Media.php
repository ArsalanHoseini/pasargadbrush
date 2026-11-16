<?php

namespace App\Models;

use App\Services\Media\ImageThumbnailer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'disk',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * A WebP copy at most $width pixels wide (one of ImageThumbnailer::WIDTHS),
     * falling back to the original when one can't be made.
     */
    public function thumbnailUrl(int $width = 480): string
    {
        return app(ImageThumbnailer::class)->url($this, $width);
    }

    /**
     * What the admin media picker needs to show and submit this image.
     *
     * @return array{id: int, url: string, thumb: string, alt: string, name: string, width: int|null, height: int|null, size_kb: int, usage_count: int}
     */
    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'thumb' => $this->thumbnailUrl(480),
            'alt' => (string) $this->alt_text,
            'name' => basename($this->path),
            'width' => $this->width,
            'height' => $this->height,
            'size_kb' => (int) round(((int) $this->size) / 1024),
            'usage_count' => (int) ($this->usage_count ?? 0),
        ];
    }
}
