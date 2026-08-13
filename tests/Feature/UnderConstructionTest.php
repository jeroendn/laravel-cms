<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnderConstructionTest extends TestCase
{
    use RefreshDatabase;

    public function testThePublicPagesAreUnaffectedOutsideProduction(): void
    {
        Page::factory()->visible()->create(['title' => 'Magnesium and sleep', 'slug' => 'sleep']);

        $response = $this->get('/sleep');

        $response->assertOk();
        $response->assertSee('Magnesium and sleep');
    }

    public function testAGuestOnlySeesThePlaceholderOnProduction(): void
    {
        PageGroup::factory()->create(['slug' => 'health']);
        Page::factory()->visible()->create(['title' => 'Magnesium and sleep', 'slug' => 'sleep']);
        $this->runningOnProduction();

        foreach (['/', '/sleep', '/health'] as $url) {
            $response = $this->get($url);

            $response->assertServiceUnavailable();
            $response->assertViewIs('under-construction');
            $response->assertSee(__('This website is under construction'));
            $response->assertDontSee('Magnesium and sleep');
        }
    }

    /**
     * The binders resolve the path before the route middleware runs, so a
     * junk URL stays a 404 instead of suggesting there is a page there.
     */
    public function testAnUnknownPathIs404NotThePlaceholder(): void
    {
        $this->runningOnProduction();

        $this->get('/nope')->assertNotFound();
        $this->get('/nope/nope')->assertNotFound();
        $this->get('/nope/nope/nope')->assertNotFound();
    }

    /** The health route is registered before the catch-alls can swallow it. */
    public function testTheHealthEndpointStaysReachable(): void
    {
        $this->runningOnProduction();

        $this->get('/up')->assertOk();
    }

    public function testAnAuthenticatedUserStillSeesTheRealSite(): void
    {
        Page::factory()->visible()->create(['title' => 'Magnesium and sleep', 'slug' => 'sleep']);
        $this->runningOnProduction();

        $response = $this->actingAs(User::factory()->create())->get('/sleep');

        $response->assertOk();
        $response->assertSee('Magnesium and sleep');
    }

    /** Without a way in, the placeholder would lock out the admin too. */
    public function testAGuestCanStillReachTheLoginPage(): void
    {
        $this->runningOnProduction();

        $this->get(route('login'))->assertOk();
        $this->get(route('password.request'))->assertOk();
    }

    public function testTheAdminAreaKeepsWorkingBehindThePlaceholder(): void
    {
        $this->runningOnProduction();

        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard'));

        $response->assertOk();
    }

    /** The placeholder must not be indexed as the site's content. */
    public function testThePlaceholderIsNotIndexable(): void
    {
        $this->runningOnProduction();

        $this->get(route('home'))->assertSee('<meta name="robots" content="noindex">', false);
    }

    /**
     * `App::isProduction()` reads the container's `env` binding, which is
     * resolved once at boot — setting `config(['app.env' => …])` here would
     * not reach it.
     */
    private function runningOnProduction(): void
    {
        $this->app['env'] = 'production';
    }
}
