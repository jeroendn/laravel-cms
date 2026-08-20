<?php

namespace App\Providers;

use Override;
use App\Models\Page;
use App\Models\PageGroup;
use App\Models\Setting;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->scoped(Setting::class, fn(): Setting => Setting::query()->first() ?? new Setting());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Baseline for anything outside a request (console, mail); the
        // SetLocale middleware overrides both locales per request.
        Carbon::setLocale(config()->string('app.locale'));

        Blade::if('adminArea', fn(): bool => request()->routeIs('admin.*'));

        $this->registerPageBindings();
    }

    /**
     * Binders for the public catch-all routes. Binding happens in the web
     * group's SubstituteBindings, before route middleware runs — so an
     * unknown path 404s before UnderConstruction can answer with the
     * placeholder. Parameters bind in URI order: by the time {item} binds,
     * {group} and {subgroup} already hold their models. A slug matches at
     * most one of page/group per level (cross-table unique, see the
     * requests), so the page-then-group lookups cannot be ambiguous.
     */
    private function registerPageBindings(): void
    {
        Route::bind('group', fn(string $value): PageGroup => PageGroup::query()
            ->whereNull('parent_id')
            ->where('slug', $value)
            ->firstOrFail());

        Route::bind('subgroup', function (string $value, RoutingRoute $route): PageGroup {
            $group = $route->parameter('group');
            assert($group instanceof PageGroup);

            return $group->children()->where('slug', $value)->firstOrFail();
        });

        Route::bind('item', function (string $value, RoutingRoute $route): Page|PageGroup {
            $subgroup = $route->parameter('subgroup');

            if ($subgroup instanceof PageGroup) {
                return $subgroup->pages()->where('slug', $value)->firstOrFail();
            }

            $group = $route->parameter('group');

            if ($group instanceof PageGroup) {
                return $group->pages()->where('slug', $value)->first()
                    ?? $group->children()->where('slug', $value)->firstOrFail();
            }

            return Page::query()->whereNull('page_group_id')->where('slug', $value)->first()
                ?? PageGroup::query()->whereNull('parent_id')->where('slug', $value)->firstOrFail();
        });
    }
}
