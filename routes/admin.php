<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductReviewController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\QuoteRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SeoRedirectController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WordpressImportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

/*
 * Every staff member (admin and editor): dashboard, search, own profile,
 * catalog and content.
 */
Route::middleware(['auth', 'admin.staff'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::resource('categories', ProductCategoryController::class)
        ->parameters(['categories' => 'product_category'])
        ->except('show');

    Route::resource('blog-categories', BlogCategoryController::class)->except('show');
    Route::resource('blog-posts', BlogPostController::class)->except('show');
    Route::post('/products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
    Route::resource('products', ProductController::class)->except('show');

    Route::resource('applications', ApplicationController::class)->except('show');

    Route::post('/product-reviews/bulk', [ProductReviewController::class, 'bulk'])->name('product-reviews.bulk');
    Route::get('/product-reviews', [ProductReviewController::class, 'index'])->name('product-reviews.index');
    Route::post('/product-reviews/{productReview}/approve', [ProductReviewController::class, 'approve'])->name('product-reviews.approve');
    Route::post('/product-reviews/{productReview}/spam', [ProductReviewController::class, 'spam'])->name('product-reviews.spam');
    Route::delete('/product-reviews/{productReview}', [ProductReviewController::class, 'destroy'])->name('product-reviews.destroy');

    Route::post('/comments/bulk', [CommentController::class, 'bulk'])->name('comments.bulk');
    Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
    Route::post('/comments/{comment}/spam', [CommentController::class, 'spam'])->name('comments.spam');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::resource('pages', PageController::class)->except('show');

    /*
     * Admins only: sales, customers, messages, reports, settings, staff
     * accounts and the activity log (docs/DECISIONS.md — staff roles).
     */
    Route::middleware('can:admin')->group(function () {
        Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
        Route::post('/orders/bulk', [OrderController::class, 'bulk'])->name('orders.bulk');
        Route::post('/orders/{order}/notes', [OrderController::class, 'storeNote'])->name('orders.notes.store');
        Route::get('/orders/{order}/print/{document}', [OrderController::class, 'print'])->whereIn('document', ['invoice', 'label'])->name('orders.print');
        Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'edit', 'update']);

        Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::resource('customers', CustomerController::class)->only(['index', 'show']);

        Route::get('/quote-requests/export', [QuoteRequestController::class, 'export'])->name('quote-requests.export');
        Route::post('/quote-requests/bulk', [QuoteRequestController::class, 'bulk'])->name('quote-requests.bulk');
        Route::resource('quote-requests', QuoteRequestController::class)->only(['index', 'edit', 'update', 'destroy']);

        Route::get('/conversations/attachments/{attachment}', [ConversationController::class, 'attachment'])->name('conversations.attachment');
        Route::resource('conversations', ConversationController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('/conversations/{conversation}/reply', [ConversationController::class, 'reply'])->name('conversations.reply');
        Route::put('/conversations/{conversation}/status', [ConversationController::class, 'updateStatus'])->name('conversations.status');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/shipping', [ShippingSettingController::class, 'edit'])->name('shipping.edit');
        Route::put('/shipping', [ShippingSettingController::class, 'update'])->name('shipping.update');

        Route::resource('redirects', SeoRedirectController::class)->except('show');

        Route::get('/import', [WordpressImportController::class, 'index'])->name('import.index');
        Route::post('/import/{step}', [WordpressImportController::class, 'run'])->name('import.run');

        Route::resource('users', UserController::class)->except('show');
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });
});
