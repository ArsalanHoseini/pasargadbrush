<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCustomizationController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/llms.txt', [SitemapController::class, 'llms'])->name('llms');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

Route::post('/quote-requests', [QuoteRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('quote-requests.store');

Route::get('/categories/{category:slug}', [ProductCategoryController::class, 'show'])->name('categories.show');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product:slug}/reviews', [ProductReviewController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('products.reviews.store');
Route::get('/products/{product:slug}/customize', [ProductCustomizationController::class, 'create'])->name('products.customize');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/feed.xml', [BlogController::class, 'feed'])->name('blog.feed');
Route::get('/blog/tag/{tag:slug}', [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{post:slug}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('blog.comments.store');

Route::get('/page/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('checkout.store');
Route::get('/checkout/confirmation', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

Route::get('/order-tracking', [OrderTrackingController::class, 'create'])->name('orders.track.create');
Route::post('/order-tracking', [OrderTrackingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('orders.track.store');
Route::get('/order-tracking/status', [OrderTrackingController::class, 'show'])->name('orders.track.show');

/*
| Customer accounts — login by SMS code or password (see Account\LoginController).
*/
Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [Account\LoginController::class, 'create'])->name('account.login');
    Route::post('/login/code', [Account\LoginController::class, 'sendCode'])
        ->middleware('throttle:10,1')
        ->name('account.login.code');
    Route::get('/login/verify', [Account\LoginController::class, 'showVerify'])->name('account.login.verify');
    Route::post('/login/verify', [Account\LoginController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('account.login.verify.store');
    Route::post('/login/password', [Account\LoginController::class, 'passwordLogin'])
        ->middleware('throttle:10,1')
        ->name('account.login.password');
});

Route::middleware('auth:customer')->prefix('account')->name('account.')->group(function () {
    Route::post('/logout', [Account\LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [Account\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders', [Account\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [Account\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/reorder', [Account\OrderController::class, 'reorder'])->name('orders.reorder');
    Route::get('/quote-requests', [Account\QuoteRequestController::class, 'index'])->name('quote-requests.index');
    Route::get('/profile', [Account\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Account\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [Account\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::resource('addresses', Account\AddressController::class)->except('show');

    Route::get('/messages', [Account\ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/messages/create', [Account\ConversationController::class, 'create'])->name('conversations.create');
    Route::post('/messages', [Account\ConversationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('conversations.store');
    Route::get('/messages/attachments/{attachment}', [Account\ConversationController::class, 'attachment'])->name('conversations.attachment');
    Route::get('/messages/{conversation}', [Account\ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/messages/{conversation}/reply', [Account\ConversationController::class, 'reply'])
        ->middleware('throttle:20,1')
        ->name('conversations.reply');
});
