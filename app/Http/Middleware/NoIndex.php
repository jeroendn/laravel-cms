<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps everything that is not public content out of search indexes. A header
 * rather than a meta tag: it also covers the redirects laravel/ui's own views
 * produce, which no Blade template of ours renders.
 */
class NoIndex
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }
}
