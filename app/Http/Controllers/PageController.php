<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageGroup;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * The home page: the ungrouped page slugged "home", or a bare layout
     * while no visible one exists.
     */
    public function home(): View
    {
        return view('home', [
            'page' => Page::visible()
                ->whereNull('page_group_id')
                ->where('slug', 'home')
                ->first(),
        ]);
    }

    /**
     * The three depths get their own method: route parameters are passed
     * to the controller positionally, so the deepest (already validated)
     * segment must be the last parameter of each signature.
     */
    public function show(Page|PageGroup $item): View
    {
        return $this->render($item);
    }

    public function showInGroup(PageGroup $group, Page|PageGroup $item): View
    {
        return $this->render($item);
    }

    public function showInSubgroup(PageGroup $group, PageGroup $subgroup, Page $item): View
    {
        return $this->render($item);
    }

    /**
     * A page, or a group's overview of its own visible pages plus its
     * subgroups. The binders already resolved the path; visibility stays
     * a concern of this controller.
     */
    private function render(Page|PageGroup $item): View
    {
        if ($item instanceof PageGroup) {
            return view('pages.group', [
                'group' => $item,
                'pages' => Page::visible()
                    ->where('page_group_id', $item->id)
                    ->latest('published_at')
                    ->simplePaginate(10),
                'subgroups' => $item->children()->orderBy('name')->get(),
            ]);
        }

        abort_unless($item->isVisible(), 404);

        return view('pages.show', ['page' => $item]);
    }
}
