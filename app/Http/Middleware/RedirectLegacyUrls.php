<?php

namespace App\Http\Middleware;

use App\Models\SeoRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns a would-be 404 into its SEO redirect (or a 410 Gone), so the old
 * WordPress site's URLs and old slugs keep their search rankings. It runs
 * after the app has tried to handle the request, so a real page always wins
 * and nothing is looked up for normal traffic.
 */
class RedirectLegacyUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404 || ! $request->isMethodSafe() || $request->is('admin', 'admin/*')) {
            return $response;
        }

        $redirect = SeoRedirect::resolve($request->getPathInfo());

        if ($redirect === null) {
            return $response;
        }

        // Plain query, not a model save — the model's saved event would flush the cached map on every hit.
        DB::table('seo_redirects')->where('id', $redirect['id'])->increment('hits_count');

        if ($redirect['status_code'] === 410 || $redirect['to_path'] === null) {
            return response()->view('errors.410', [], 410);
        }

        return redirect()->to($this->target($redirect['to_path']), $redirect['status_code']);
    }

    /**
     * Targets are stored as paths ("/products/برس-اسب"); percent-encode the
     * segments so the Location header is a valid URL.
     */
    private function target(string $toPath): string
    {
        if (preg_match('#^https?://#i', $toPath)) {
            return $toPath;
        }

        [$path, $query] = array_pad(explode('?', $toPath, 2), 2, null);
        $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));

        return url($encoded).($query !== null ? '?'.$query : '');
    }
}
