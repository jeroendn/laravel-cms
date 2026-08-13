<?php

namespace App\Support;

use App\Models\Page;
use App\Models\PageGroup;
use Closure;
use Illuminate\Database\Eloquent\Collection;

class Menu
{
    /**
     * The public menu: menu-toggled root groups and ungrouped visible
     * pages. Groups become dropdowns of their own menu-toggled pages and
     * subgroups (recursively, one level), closed by a "Show All" link to
     * the group overview. Two queries; the tree and the ordering happen
     * in PHP.
     *
     * @return list<MenuItem>
     */
    public static function items(): array
    {
        $groups = PageGroup::query()->where('show_in_menu', true)->get();
        $pages = Page::visible()->where('show_in_menu', true)->get();

        return array_values(collect([
            ...$groups->whereNull('parent_id'),
            ...$pages->whereNull('page_group_id'),
        ])
            ->sort(self::comparator())
            ->map(fn(Page|PageGroup $model): MenuItem => $model instanceof Page
                ? self::pageItem($model)
                : self::groupItem($model, $groups, $pages))
            ->all());
    }

    /**
     * @param Collection<int, PageGroup> $groups
     * @param Collection<int, Page> $pages
     */
    private static function groupItem(PageGroup $group, Collection $groups, Collection $pages): MenuItem
    {
        $children = collect([
            ...$pages->where('page_group_id', $group->id),
            ...$groups->where('parent_id', $group->id),
        ])
            ->sort(self::comparator())
            ->map(fn(Page|PageGroup $model): MenuItem => $model instanceof Page
                ? self::pageItem($model)
                : self::groupItem($model, $groups, $pages))
            ->values();

        $children->push(new MenuItem(__('Show All'), $group->url(), false));

        return new MenuItem(
            $group->name,
            $group->url(),
            request()->is($group->path()) || request()->is($group->path() . '/*'),
            array_values($children->all()),
        );
    }

    private static function pageItem(Page $page): MenuItem
    {
        return new MenuItem($page->title, $page->url(), request()->url() === $page->url());
    }

    /**
     * The shared ordering: priority DESC (higher sorts further left),
     * ties alphabetically and case-insensitively.
     *
     * @return Closure(Page|PageGroup, Page|PageGroup): int
     */
    private static function comparator(): Closure
    {
        $key = fn(Page|PageGroup $model): array => [
            -$model->priority,
            mb_strtolower($model instanceof Page ? $model->title : $model->name),
        ];

        return fn(Page|PageGroup $a, Page|PageGroup $b): int => $key($a) <=> $key($b);
    }
}
