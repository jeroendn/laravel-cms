<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageGroupRequest;
use App\Http\Requests\UpdatePageGroupRequest;
use App\Models\PageGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

class PageGroupController extends Controller
{
    public function index(): View
    {
        return view('admin.page-groups.index', [
            'groups' => PageGroup::query()
                ->with('parent')
                ->orderBy('name')
                ->simplePaginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.page-groups.create', ['parents' => self::parentOptions()]);
    }

    public function store(StorePageGroupRequest $request): RedirectResponse
    {
        PageGroup::create([
            ...$request->safe(['name', 'slug', 'parent_id']),
            'show_in_menu' => $request->boolean('show_in_menu'),
            'priority' => $request->integer('priority'),
        ]);

        return redirect()
            ->route('admin.page-groups.index')
            ->with('status', __(':Name created.', ['name' => __('page group')]));
    }

    public function edit(PageGroup $pageGroup): View
    {
        return view('admin.page-groups.edit', [
            'group' => $pageGroup,
            'parents' => self::parentOptions($pageGroup),
        ]);
    }

    public function update(UpdatePageGroupRequest $request, PageGroup $pageGroup): RedirectResponse
    {
        $pageGroup->update([
            ...$request->safe(['name', 'slug', 'parent_id']),
            'show_in_menu' => $request->boolean('show_in_menu'),
            'priority' => $request->integer('priority'),
        ]);

        return redirect()
            ->route('admin.page-groups.index')
            ->with('status', __(':Name updated.', ['name' => __('page group')]));
    }

    public function destroy(PageGroup $pageGroup): RedirectResponse
    {
        if ($pageGroup->children()->exists()) {
            return redirect()
                ->route('admin.page-groups.index')
                ->with('error', __('Cannot delete :name: it still contains pages or subgroups.', ['name' => $pageGroup->name]));
        }

        $pageGroup->delete();

        return redirect()
            ->route('admin.page-groups.index')
            ->with('status', __(':Name deleted.', ['name' => __('page group')]));
    }

    /**
     * Groups selectable as parent: root groups, minus the group being edited.
     *
     * @return Collection<int, PageGroup>
     */
    private static function parentOptions(?PageGroup $except = null): Collection
    {
        $query = PageGroup::query()->whereNull('parent_id')->orderBy('name');

        if ($except !== null) {
            $query->whereKeyNot($except->id);
        }

        return $query->get();
    }
}
