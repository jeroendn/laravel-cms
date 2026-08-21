<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Remember the visitor's language choice. A POST on purpose: every page
     * keeps one URL, so there are no duplicate-content variants to index.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locales::isEnabled($locale), 404);

        $request->session()->put('locale', $locale);

        return back();
    }
}
