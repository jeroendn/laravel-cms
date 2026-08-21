<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageGroup;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnderConstructionTest extends TestCase
{
    use RefreshDatabase;

    public function testThePublicPagesAreUnaffectedWhileTheSettingIsOff(): void
    {
        Page::factory()->visible()->create(['title' => 'Season of Mists', 'slug' => 'season-of-mists']);

        $response = $this->get('/season-of-mists');

        $response->assertOk();
        $response->assertSee('Season of Mists');
    }

    public function testAGuestOnlySeesThePlaceholderWhileUnderConstruction(): void
    {
        PageGroup::factory()->create(['slug' => 'the-endless']);
        Page::factory()->visible()->create(['title' => 'Season of Mists', 'slug' => 'season-of-mists']);
        $this->underConstruction();

        foreach (['/', '/season-of-mists', '/the-endless'] as $url) {
            $response = $this->get($url);

            $response->assertServiceUnavailable();
            $response->assertViewIs('under-construction');
            $response->assertSee(__('This website is under construction'));
            $response->assertDontSee('Season of Mists');
        }
    }

    /**
     * The binders resolve the path before the route middleware runs, so a
     * junk URL stays a 404 instead of suggesting there is a page there.
     */
    public function testAnUnknownPathIs404NotThePlaceholder(): void
    {
        $this->underConstruction();

        $this->get('/nope')->assertNotFound();
        $this->get('/nope/nope')->assertNotFound();
        $this->get('/nope/nope/nope')->assertNotFound();
    }

    /** The health route is registered before the catch-alls can swallow it. */
    public function testTheHealthEndpointStaysReachable(): void
    {
        $this->underConstruction();

        $this->get('/up')->assertOk();
    }

    public function testAnAuthenticatedUserStillSeesTheRealSite(): void
    {
        Page::factory()->visible()->create(['title' => 'Season of Mists', 'slug' => 'season-of-mists']);
        $this->underConstruction();

        $response = $this->actingAs(User::factory()->create())->get('/season-of-mists');

        $response->assertOk();
        $response->assertSee('Season of Mists');
    }

    /** Without a way in, the placeholder would lock out the admin too. */
    public function testAGuestCanStillReachTheLoginPage(): void
    {
        $this->underConstruction();

        $this->get(route('login'))->assertOk();
        $this->get(route('password.request'))->assertOk();
    }

    public function testTheAdminAreaKeepsWorkingBehindThePlaceholder(): void
    {
        $this->underConstruction();

        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard'));

        $response->assertOk();
    }

    /** The placeholder must not be indexed as the site's content. */
    public function testThePlaceholderIsNotIndexable(): void
    {
        $this->underConstruction();

        $this->get(route('home'))->assertSee('<meta name="robots" content="noindex">', false);
    }

    /**
     * Tests\TestCase turns the flag off for every test, since the migration
     * seeds it on; these are the ones that want it on.
     */
    private function underConstruction(): void
    {
        Setting::current()->update(['under_construction' => true]);
    }
}
