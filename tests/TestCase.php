<?php

namespace Tests;

use Override;
use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // `public/build/` is a build artifact (gitignored), so on CI the
        // `@vite` directive in the layout would throw a
        // ViteManifestNotFoundException. Tests never assert on asset tags;
        // the separate `assets` job guards the build itself.
        $this->withoutVite();

        // The migration seeds a fresh site as under construction, which would
        // answer 503 to every public page a test asks for.
        // UnderConstructionTest turns it back on where that is the point.
        // Guarded: the tests without RefreshDatabase have no tables at all.
        if (Schema::hasTable('settings')) {
            Setting::current()->update(['under_construction' => false]);
        }
    }
}
