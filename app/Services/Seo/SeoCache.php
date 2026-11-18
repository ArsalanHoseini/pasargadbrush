<?php

namespace App\Services\Seo;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Tag;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Cache for the machine-readable outputs (/llms.txt, /llms-full.txt, the
 * articles' .md copies). This app's «database» cache store has no tags, so a
 * version number stands in for one: every key carries it, and saving or
 * deleting any content the outputs show bumps it — the admin's «ذخیره»
 * makes them fresh on the next request, and old entries simply expire.
 * Only strings are cached (see docs/DECISIONS.md, never cache models).
 */
class SeoCache
{
    private const VERSION_KEY = 'seo.version';

    private const TTL_SECONDS = 3600;

    /**
     * Models whose save/delete changes what the outputs say.
     *
     * @var list<class-string<Model>>
     */
    public const WATCHED_MODELS = [
        BlogPost::class,
        BlogCategory::class,
        Tag::class,
        Page::class,
        Product::class,
        ProductVariant::class,
        ProductCategory::class,
        Setting::class,
    ];

    /**
     * @param  Closure(): string  $build
     */
    public static function remember(string $key, Closure $build): string
    {
        // The cache lives in the database too: without it, build uncached.
        return rescue(fn () => Cache::remember('seo.'.self::version().'.'.$key, self::TTL_SECONDS, $build), $build);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function watchModels(): void
    {
        foreach (self::WATCHED_MODELS as $model) {
            $model::saved(fn () => self::flush());
            $model::deleted(fn () => self::flush());
        }
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
