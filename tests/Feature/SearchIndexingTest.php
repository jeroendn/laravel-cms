<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearchIndexingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function authRoutes(): array
    {
        return [
            'login' => ['login'],
            'password request' => ['password.request'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'pages' => ['admin.pages.index'],
            'page groups' => ['admin.page-groups.index'],
            'users' => ['admin.users.index'],
        ];
    }

    #[DataProvider('authRoutes')]
    public function testTheAuthPagesAreNotIndexable(string $route): void
    {
        $response = $this->get(route($route));

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex');
    }

    #[DataProvider('adminRoutes')]
    public function testTheAdminPagesAreNotIndexable(string $route): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route($route));

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex');
    }

    /**
     * A crawler is a guest, so it never gets past this redirect — and the page
     * it lands on says noindex itself. The redirect carries no header: `auth`
     * throws before the middleware ever sees a response.
     */
    public function testACrawlerOnlyEverReachesTheLoginPage(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->get(route('login'))->assertHeader('X-Robots-Tag', 'noindex');
    }

    public function testThePublicPagesStayIndexable(): void
    {
        $page = Page::factory()->visible()->create();

        foreach ([route('home'), route('pages.index'), route('pages.show', $page)] as $url) {
            $response = $this->get($url);

            $response->assertOk();
            $response->assertHeaderMissing('X-Robots-Tag');
        }
    }

    /**
     * Crawling must stay allowed: a disallowed URL can still be listed, and a
     * crawler that never fetches the page never reads the noindex either.
     */
    public function testRobotsTxtDisallowsNothing(): void
    {
        $this->assertStringNotContainsString(
            'Disallow: /',
            (string) file_get_contents(public_path('robots.txt')),
        );
    }
}
