<?php

namespace App\Providers;

use App\Models\ProductCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['partials.header', 'partials.footer'], function ($view) {
            // Cache plain arrays, not Eloquent models/Collections — caching
            // objects is fragile across this app's cache-driver (de)serialization
            // boundary (see docs/DECISIONS.md).
            $categories = Cache::remember(
                'nav.categories',
                now()->addHour(),
                fn () => ProductCategory::query()
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug'])
                    ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug])
                    ->all(),
            );

            $view->with('navCategories', $categories);
        });
    }
}
