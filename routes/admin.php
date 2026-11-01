<?php

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
use App\Http\Controllers\Admin\QuoteRequestController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingSettingController;
use App\Http\Controllers\Admin\WordpressImportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware(['auth', 'admin.staff'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/import', [WordpressImportController::class, 'index'])->name('import.index');
    Route::post('/import/{step}', [WordpressImportController::class, 'run'])->name('import.run');

    Route::resource('categories', ProductCategoryController::class)
        ->parameters(['categories' => 'product_category'])
        ->except('show');

    Route::resource('blog-categories', BlogCategoryController::class)->except('show');
    Route::resource('blog-posts', BlogPostController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');

    Route::resource('applications', ApplicationController::class)->except('show');

    Route::get('/product-reviews', [ProductReviewController::class, 'index'])->name('product-reviews.index');
    Route::post('/product-reviews/{productReview}/approve', [ProductReviewController::class, 'approve'])->name('product-reviews.approve');
    Route::post('/product-reviews/{productReview}/spam', [ProductReviewController::class, 'spam'])->name('product-reviews.spam');
    Route::delete('/product-reviews/{productReview}', [ProductReviewController::class, 'destroy'])->name('product-reviews.destroy');

    Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
    Route::post('/comments/{comment}/spam', [CommentController::class, 'spam'])->name('comments.spam');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::resource('quote-requests', QuoteRequestController::class)->only(['index', 'edit', 'update', 'destroy']);

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/shipping', [ShippingSettingController::class, 'edit'])->name('shipping.edit');
    Route::put('/shipping', [ShippingSettingController::class, 'update'])->name('shipping.update');

    Route::resource('pages', PageController::class)->except('show');

    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('customers', CustomerController::class)->only(['index', 'show']);

    Route::get('/conversations/attachments/{attachment}', [ConversationController::class, 'attachment'])->name('conversations.attachment');
    Route::resource('conversations', ConversationController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/conversations/{conversation}/reply', [ConversationController::class, 'reply'])->name('conversations.reply');
    Route::put('/conversations/{conversation}/status', [ConversationController::class, 'updateStatus'])->name('conversations.status');
});
