<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A permanent redirect (or a 410 "gone") for a URL that no longer exists —
 * mainly the old WordPress site's URLs, so search rankings and old links
 * carry over. Only consulted when a request would otherwise be a 404
 * (see App\Http\Middleware\RedirectLegacyUrls), so it can never shadow a
 * real page.
 */
class SeoRedirect extends Model
{
    public const SOURCE_WORDPRESS = 'wordpress';

    public const SOURCE_AUTO = 'auto';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_LABELS = [
        self::SOURCE_WORDPRESS => 'سایت قدیمی (وردپرس)',
        self::SOURCE_AUTO => 'خودکار (تغییر نامک)',
        self::SOURCE_MANUAL => 'دستی',
    ];

    private const CACHE_KEY = 'seo_redirects.map';

    protected $fillable = [
        'from_path',
        'to_path',
        'status_code',
        'source',
        'notes',
        'hits_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status_code' => 'integer',
        'hits_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * One canonical form for comparing paths: percent-decoded (old WordPress
     * Persian slugs arrive both encoded and raw), a single leading slash, no
     * trailing slash, lower-case, no query string.
     */
    public static function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        $path = mb_strtolower(rawurldecode($path));
        $path = '/'.trim($path, '/');

        return preg_replace('#/+#', '/', $path) ?? $path;
    }

    /**
     * Records that content moved from $oldPath to $newPath: a 301 from the
     * old path, and any redirect that pointed at the old path now points
     * straight at the new one (no redirect chains).
     */
    public static function recordMove(string $oldPath, string $newPath): void
    {
        $from = self::normalizePath($oldPath);

        if ($from === self::normalizePath($newPath)) {
            return;
        }

        static::query()->whereIn('to_path', [$oldPath, $from])->update(['to_path' => $newPath]);
        static::query()->where('from_path', self::normalizePath($newPath))->delete();

        static::query()->updateOrCreate(['from_path' => $from], [
            'to_path' => $newPath,
            'status_code' => 301,
            'source' => self::SOURCE_AUTO,
            'notes' => 'Slug changed',
            'is_active' => true,
        ]);

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The redirect for a request path, also trying the forms the old site
     * produced for the same content: date-prefixed post URLs
     * (/2013/04/02/slug) and WordPress's resized image copies
     * (photo-300x200.jpg).
     *
     * @return array{id: int, to_path: ?string, status_code: int}|null
     */
    public static function resolve(string $path): ?array
    {
        $map = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->where('is_active', true)
            ->get(['id', 'from_path', 'to_path', 'status_code'])
            ->mapWithKeys(fn (self $redirect) => [$redirect->from_path => [
                'id' => $redirect->id,
                'to_path' => $redirect->to_path,
                'status_code' => $redirect->status_code,
            ]])
            ->all());

        $path = self::normalizePath($path);

        $candidates = array_unique([
            $path,
            preg_replace('#^/\d{4}/\d{2}(?:/\d{2})?/#', '/', $path),
            preg_replace('#-\d+x\d+(\.[a-z0-9]+)$#', '$1', $path),
        ]);

        foreach ($candidates as $candidate) {
            if (isset($map[$candidate])) {
                return $map[$candidate];
            }
        }

        return null;
    }
}
