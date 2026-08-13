<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()
                ->latest()
                ->simplePaginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        Page::create([
            ...$request->safe(['title', 'slug', 'body']),
            'published_at' => $request->date('published_at'),
        ]);

        return redirect()
            ->route('admin.pages.index')
            ->with('status', __(':Name created.', ['name' => __('page')]));
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', ['page' => $page]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $page->update([
            ...$request->safe(['title', 'slug', 'body']),
            'published_at' => $request->date('published_at'),
        ]);

        return redirect()
            ->route('admin.pages.index')
            ->with('status', __(':Name updated.', ['name' => __('page')]));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', __(':Name deleted.', ['name' => __('page')]));
    }
}
