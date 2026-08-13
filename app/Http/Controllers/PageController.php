<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * How many pages the home page teases before pointing at the archive.
     */
    private const int RECENT = 5;

    public function home(): View
    {
        return view('home', [
            'pages' => Page::visible()
                ->latest('published_at')
                ->take(self::RECENT)
                ->get(),
        ]);
    }

    public function index(): View
    {
        return view('pages.index', [
            'pages' => Page::visible()
                ->latest('published_at')
                ->simplePaginate(10),
        ]);
    }

    public function show(Page $page): View
    {
        abort_unless($page->isVisible(), 404);

        return view('pages.show', ['page' => $page]);
    }
}
