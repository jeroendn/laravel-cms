<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UnderConstructionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function publicRoutes(): array
    {
        return [
            'home' => ['home'],
            'archive' => ['pages.index'],
            'page' => ['pages.show'],
        ];
    }

    public function testThePublicPagesAreUnaffectedOutsideProduction(): void
    {
        Page::factory()->visible()->create(['title' => 'Magnesium and sleep']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Magnesium and sleep');
    }

    #[DataProvider('publicRoutes')]
    public function testAGuestOnlySeesThePlaceholderOnProduction(string $route): void
    {
        $page = Page::factory()->visible()->create(['title' => 'Magnesium and sleep']);
        $this->runningOnProduction();

        $response = $this->get(route($route, $route === 'pages.show' ? $page : []));

        $response->assertServiceUnavailable();
        $response->assertViewIs('under-construction');
        $response->assertSee(__('This website is under construction'));
        $response->assertDontSee('Magnesium and sleep');
    }

    public function testAnAuthenticatedUserStillSeesTheRealSite(): void
    {
        Page::factory()->visible()->create(['title' => 'Magnesium and sleep']);
        $this->runningOnProduction();

        $response = $this->actingAs(User::factory()->create())->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('home');
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
