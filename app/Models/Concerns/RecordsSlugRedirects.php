<?php

namespace App\Models\Concerns;

use App\Models\SeoRedirect;

/**
 * When a model's slug changes, its old public URL permanently redirects to
 * the new one — so renaming a product, category, article or page in the
 * dashboard never loses its search ranking or breaks shared links.
 */
trait RecordsSlugRedirects
{
    /**
     * The public path for a given slug, e.g. "/products/{slug}".
     */
    abstract public static function publicPathForSlug(string $slug): string;

    protected static function bootRecordsSlugRedirects(): void
    {
        static::updated(function (self $model) {
            $oldSlug = $model->getOriginal('slug');

            if ($model->wasChanged('slug') && filled($oldSlug)) {
                SeoRedirect::recordMove(
                    static::publicPathForSlug($oldSlug),
                    static::publicPathForSlug($model->slug),
                );
            }
        });
    }
}
