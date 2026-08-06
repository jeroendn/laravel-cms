<?php

namespace Tests;

use Override;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * `public/build/` is a build artifact (gitignored), so on CI the `@vite`
     * directive in the layout would throw a ViteManifestNotFoundException.
     * Tests never assert on asset tags; the separate `assets` job guards the
     * build itself.
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
