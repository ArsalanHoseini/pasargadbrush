<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * A link in one of the site's editable menus (header, footer quick links),
 * managed at /admin/menus. Top-level items may have one level of children;
 * a «categories» item shows the product categories as its dropdown.
 */
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    public const LOCATIONS = [
        'header' => 'منوی بالای سایت',
        'footer' => 'دسترسی سریع (فوتر)',
    ];

    public const TYPE_LINK = 'link';

    public const TYPE_CATEGORIES = 'categories';

    protected $fillable = ['location', 'parent_id', 'label', 'type', 'url', 'open_in_new_tab', 'sort_order'];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (MenuItem $item) => self::forgetCache($item->location));
        static::deleted(fn (MenuItem $item) => self::forgetCache($item->location));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @param  Builder<MenuItem>  $query
     */
    public function scopeTopLevel(Builder $query, string $location): void
    {
        $query->where('location', $location)->whereNull('parent_id')->orderBy('sort_order');
    }

    /**
     * The menu for one location as plain arrays (safe to cache), cached until it's edited.
     *
     * @return list<array{label: string, type: string, url: string|null, open_in_new_tab: bool, children: list<array{label: string, type: string, url: string|null, open_in_new_tab: bool}>}>
     */
    public static function tree(string $location): array
    {
        return Cache::rememberForever(self::cacheKey($location), fn () => self::query()
            ->topLevel($location)
            ->with('children')
            ->get()
            ->map(fn (MenuItem $item) => [
                ...$item->only(['label', 'type', 'url', 'open_in_new_tab']),
                'children' => $item->children->map->only(['label', 'type', 'url', 'open_in_new_tab'])->values()->all(),
            ])
            ->values()
            ->all());
    }

    /**
     * The menu ready for the public layout: site paths («/blog») become full
     * addresses on the current root URL, like the rest of the site's links.
     *
     * @return list<array<string, mixed>>
     */
    public static function forDisplay(string $location): array
    {
        $absolute = fn (array $link): array => [
            ...$link,
            'url' => is_string($link['url']) && str_starts_with($link['url'], '/') ? url($link['url']) : $link['url'],
            'path' => $link['url'],
        ];

        return array_map(fn (array $item): array => [
            ...$absolute($item),
            'children' => array_map($absolute, $item['children']),
        ], self::tree($location));
    }

    /**
     * Whether a menu URL is the page being viewed (or a section it leads to), for highlighting.
     */
    public static function isCurrentUrl(?string $url): bool
    {
        if (! $url || ! str_starts_with($url, '/')) {
            return false;
        }

        $path = '/'.ltrim(request()->path(), '/');

        return $url === '/' ? $path === '/' : str_starts_with($path, rtrim($url, '/'));
    }

    public static function forgetCache(string $location): void
    {
        Cache::forget(self::cacheKey($location));
    }

    public function isCategories(): bool
    {
        return $this->type === self::TYPE_CATEGORIES;
    }

    private static function cacheKey(string $location): string
    {
        return 'menu-items:'.$location;
    }
}
