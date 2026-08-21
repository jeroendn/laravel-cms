<?php

namespace Tests;

use Override;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

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
        // 503 every public page a test asks for; UnderConstructionTest turns
        // it back on. Without RefreshDatabase there are no tables at all.
        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            DB::table('settings')->update(['under_construction' => false]);
        }
    }
}
