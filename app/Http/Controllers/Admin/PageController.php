<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use App\Models\PageGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()
                ->with('group.parent')
                ->latest()
                ->simplePaginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.create', ['groups' => self::groupOptions()]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        Page::create([
            ...$request->safe(['title', 'slug', 'body', 'page_group_id']),
            'is_draft' => $request->boolean('is_draft'),
            'show_in_menu' => $request->boolean('show_in_menu'),
            'priority' => $request->integer('priority'),
            'published_at' => $request->date('published_at'),
        ]);

        return redirect()
            ->route('admin.pages.index')
            ->with('status', __(':Name created.', ['name' => __('page')]));
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
            'groups' => self::groupOptions(),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $page->update([
            ...$request->safe(['title', 'slug', 'body', 'page_group_id']),
            'is_draft' => $request->boolean('is_draft'),
            'show_in_menu' => $request->boolean('show_in_menu'),
            'priority' => $request->integer('priority'),
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

    /**
     * All groups for the group select, subgroups labeled and sorted with
     * their parent's name.
     *
     * @return Collection<int, PageGroup>
     */
    private static function groupOptions(): Collection
    {
        return PageGroup::query()
            ->with('parent')
            ->get()
            ->sortBy(fn(PageGroup $group): string => mb_strtolower($group->fullName()))
            ->values();
    }
}
