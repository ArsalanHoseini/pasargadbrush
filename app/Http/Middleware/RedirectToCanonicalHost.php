<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * www.pasargadbrush.com served the whole site a second time (with its own
 * canonical), splitting Google's signals between two copies. Any request on
 * the www. form of APP_URL's host is sent, with a 301, to the same path on
 * APP_URL. Other hosts (local dev, IP access) are left alone.
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonicalHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($canonicalHost && strcasecmp($request->getHost(), 'www.'.$canonicalHost) === 0) {
            return redirect()->to(rtrim((string) config('app.url'), '/').$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
