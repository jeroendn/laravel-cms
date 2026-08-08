<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class UnderConstruction
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! App::isProduction() || $request->user() !== null) {
            return $next($request);
        }

        return response()->view('under-construction', status: Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
