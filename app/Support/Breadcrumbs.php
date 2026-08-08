<?php

namespace App\Support;

use App\Models\Post;
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
            'posts.show' => self::post($route),
            'admin.posts.index' => [self::crumb(__('Posts'))],
            default => [],
        };
    }

    /**
     * @return list<array{label: string, url: string|null}>
     */
    private static function post(Route $route): array
    {
        $post = $route->parameter('post');

        return $post instanceof Post ? [self::crumb($post->title)] : [];
    }

    /**
     * @return array{label: string, url: string|null}
     */
    private static function crumb(string $label, ?string $url = null): array
    {
        return ['label' => $label, 'url' => $url];
    }
}
