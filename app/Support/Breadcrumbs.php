<?php

namespace App\Support;

use App\Models\Page;
use App\Models\PageGroup;
use Illuminate\Routing\Route;

class Breadcrumbs
{
    /**
     * The trail for the current route, home excluded — the view renders that
     * one as an icon. An empty trail hides the breadcrumbs; the admin forms
     * are deliberately in that group, they carry a back link instead.
     *
     * @return list<array{label: string, url: string|null}>
     */
    public static function current(): array
    {
        $route = request()->route();

        if (!$route instanceof Route) {
            return [];
        }

        return match ($route->getName()) {
            'pages.show', 'pages.grouped', 'pages.subgrouped' => self::publicItem($route),
            'admin.pages.index' => [self::crumb(__('Pages'))],
            'admin.page-groups.index' => [self::crumb(__('Page groups'))],
            'admin.users.index' => [self::crumb(__('Users'))],
            'admin.settings.edit' => [self::crumb(__('Settings'))],
            default => [],
        };
    }

    public static function homeUrl(): string
    {
        return request()->routeIs('admin.*') ? route('admin.dashboard') : route('home');
    }

    /**
     * The trail of a dynamic page/group URL, walking the bound model's
     * ancestors: 🏠 / group / subgroup / page, as deep as the URL goes.
     *
     * @return list<array{label: string, url: string|null}>
     */
    private static function publicItem(Route $route): array
    {
        $item = $route->parameter('item');

        if ($item instanceof PageGroup) {
            return self::groupTrail($item);
        }

        if (!$item instanceof Page) {
            return [];
        }

        $trail = $item->group === null ? [] : self::groupTrail($item->group, linkLast: true);
        $trail[] = self::crumb($item->title);

        return $trail;
    }

    /**
     * A group and its parent; the group itself only links when a page
     * crumb follows it.
     *
     * @return list<array{label: string, url: string|null}>
     */
    private static function groupTrail(PageGroup $group, bool $linkLast = false): array
    {
        $trail = [];

        if ($group->parent !== null) {
            $trail[] = self::crumb($group->parent->name, $group->parent->url());
        }

        $trail[] = self::crumb($group->name, $linkLast ? $group->url() : null);

        return $trail;
    }

    /**
     * @return array{label: string, url: string|null}
     */
    private static function crumb(string $label, ?string $url = null): array
    {
        return ['label' => $label, 'url' => $url];
    }
}
