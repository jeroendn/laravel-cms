<?php

namespace App\Providers;

use Override;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Blade formats dates with translatedFormat(); Carbon does not pick
        // up the app locale by itself.
        Carbon::setLocale(config()->string('app.locale'));
    }
}
