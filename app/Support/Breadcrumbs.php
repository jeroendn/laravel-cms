<?php

namespace App\Support;

use App\Models\Page;
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
            'pages.index' => [self::crumb(__('Pages'))],
            'pages.show' => self::page($route),
            'admin.pages.index' => [self::crumb(__('Pages'))],
            'admin.page-groups.index' => [self::crumb(__('Page groups'))],
            'admin.users.index' => [self::crumb(__('Users'))],
            default => [],
        };
    }

    public static function homeUrl(): string
    {
        return request()->routeIs('admin.*') ? route('admin.dashboard') : route('home');
    }

    /**
     * @return list<array{label: string, url: string|null}>
     */
    private static function page(Route $route): array
    {
        $page = $route->parameter('page');

        if (!$page instanceof Page) {
            return [];
        }

        return [
            self::crumb(__('Pages'), route('pages.index')),
            self::crumb($page->title),
        ];
    }

    /**
     * @return array{label: string, url: string|null}
     */
    private static function crumb(string $label, ?string $url = null): array
    {
        return ['label' => $label, 'url' => $url];
    }
}
