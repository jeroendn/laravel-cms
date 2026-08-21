<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnderConstruction
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::current()->under_construction || $request->user() !== null) {
            return $next($request);
        }

        return response()->view('under-construction', status: Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
