<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

Route::post('/quote-requests', [QuoteRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('quote-requests.store');

Route::get('/categories/{category:slug}', [ProductCategoryController::class, 'show'])->name('categories.show');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

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
