<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen language. The choice rides along in the
 * session (LanguageController writes it), so every page keeps a single URL —
 * only the chrome translates, page content stays as it was typed.
 */
class SetLocale
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $chosen = $request->session()->get('locale');
        $locale = is_string($chosen) && Locales::isEnabled($chosen)
            ? $chosen
            : Locales::defaultLocale();

        App::setLocale($locale);
        // Blade formats dates with translatedFormat(); Carbon does not follow
        // the app locale by itself.
        Carbon::setLocale($locale);

        return $next($request);
    }
}
