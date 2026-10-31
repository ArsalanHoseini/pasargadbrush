<?php

namespace App\Providers;

use App\Models\ProductCategory;
use App\Services\Cart\CartResolver;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
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
        $this->app->bind(SmsSender::class, fn () => match (config('services.sms.driver')) {
            default => new LogSmsSender,
        });
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

        View::composer('partials.header', function ($view) {
            // Per-visitor, never cached globally (that would leak one
            // visitor's cart count to everyone).
            $cart = app(CartResolver::class)->resolveForDisplay(request());
            $view->with('cartItemCount', $cart ? $cart->items->sum('quantity') : 0);
        });
    }
}
