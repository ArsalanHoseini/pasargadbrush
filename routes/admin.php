<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\QuoteRequestController;
use App\Http\Controllers\Admin\SettingController;
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

    Route::resource('quote-requests', QuoteRequestController::class)->only(['index', 'edit', 'update', 'destroy']);

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::resource('pages', PageController::class)->except('show');
});
