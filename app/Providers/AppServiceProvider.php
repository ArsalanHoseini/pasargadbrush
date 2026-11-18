<?php

namespace App\Providers;

use App\Models\MenuItem;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Cart\CartResolver;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoCache;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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

        // One JSON-LD graph per request, filled by the views and printed by the layout.
        $this->app->scoped(SchemaGraph::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Persian digits and RTL order for every ->links(); the stock view is LTR and follows OS dark mode.
        Paginator::defaultView('partials.pagination');

        // Sales, customers, settings, users, reports and the activity log are
        // admin-only; editors keep the catalog and content.
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());

        // Every generated link and canonical tag names the one real address
        // (https, no www), however the request arrived — see RedirectToCanonicalHost.
        if ($this->app->isProduction()) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // Saving content in the admin refreshes /llms.txt, /llms-full.txt and the .md copies.
        SeoCache::watchModels();

        View::composer(['partials.header', 'partials.footer'], function ($view) {
            $categories = ProductCategory::navigation();

            $view->with('navCategories', $categories);
            $view->with('headerMenu', MenuItem::forDisplay('header'));
            $view->with('footerMenu', MenuItem::forDisplay('footer'));
        });

        View::composer('partials.header', function ($view) {
            // Per-visitor, never cached globally (that would leak one
            // visitor's cart count to everyone).
            $cart = app(CartResolver::class)->resolveForDisplay(request());
            $view->with('cartItemCount', $cart ? $cart->items->sum('quantity') : 0);
        });
    }
}
