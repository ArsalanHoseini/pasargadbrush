<?php

use App\Http\Middleware\EnsureUserIsAdminStaff;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.staff' => EnsureUserIsAdminStaff::class,
        ]);

        // Staff and customers have separate logins ("web" and "customer" guards).
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('account.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.dashboard')
            : route('account.dashboard'));

        // Not sensitive (a random cart identifier, no PII) — skipping encryption
        // avoids pointless crypto overhead on every request.
        $middleware->encryptCookies(except: ['cart_token']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
