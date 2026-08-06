<?php

namespace Tests\Feature;

use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class MaintenancePageTest extends TestCase
{
    /**
     * `./deploy` prerenders this page with `artisan down --render="errors::503"`,
     * which resolves through the `errors::` namespace: our own views directory
     * first, the framework's bare fallback last. Assert the lookup really lands
     * on our file — the finder is used instead of `view()` because the
     * namespace only exists at runtime, which bladestan cannot follow.
     */
    public function testTheErrorsNamespaceResolvesToOurMaintenancePage(): void
    {
        (new RegisterErrorViewPaths())();

        $this->assertSame(
            resource_path('views/errors/503.blade.php'),
            View::getFinder()->find('errors::503'),
        );
    }

    public function testTheMaintenancePageExplainsTheSiteIsDownForMaintenance(): void
    {
        $html = $this->renderMaintenancePage();

        $this->assertStringContainsString(config()->string('app.name'), $html);
        $this->assertStringContainsString(__('Back online shortly'), $html);
    }

    /**
     * The prerendered page is echoed by public/index.php before the Composer
     * autoloader is loaded, at a point where neither vendor/ nor public/build/
     * is guaranteed to exist. Any request to another file would break it.
     */
    public function testTheMaintenancePageIsSelfContained(): void
    {
        $html = $this->renderMaintenancePage();

        $this->assertStringNotContainsString('<link', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('//fonts.', $html);
    }

    /** Search engines must not index the site while it is down. */
    public function testTheMaintenancePageIsNotIndexable(): void
    {
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex">',
            $this->renderMaintenancePage(),
        );
    }

    private function renderMaintenancePage(): string
    {
        return view('errors.503')->render();
    }
}
