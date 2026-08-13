<?php

namespace Tests\Feature;

use Symfony\Component\HttpFoundation\Response;
use App\Models\Page;
use App\Models\PageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    public function testThePagePageSitsBehindTheHomeIconAndTheArchive(): void
    {
        $page = Page::factory()->visible()->create(['title' => 'A published page']);

        $trail = $this->trail($this->get(route('pages.show', $page)));

        $this->assertStringContainsString('fa-house', $trail);
        $this->assertStringContainsString(route('pages.index'), $trail);
        $this->assertStringContainsString('A published page', $trail);
    }

    public function testTheArchiveAndTheAdminOverviewsHaveBreadcrumbs(): void
    {
        $this->assertStringContainsString('fa-house', $this->trail($this->get(route('pages.index'))));

        $admin = User::factory()->create();
        $this->assertStringContainsString(
            'fa-house',
            $this->trail($this->actingAs($admin)->get(route('admin.pages.index'))),
        );
        $this->assertStringContainsString(
            'fa-house',
            $this->trail($this->actingAs($admin)->get(route('admin.page-groups.index'))),
        );
        $this->assertStringContainsString(
            'fa-house',
            $this->trail($this->actingAs($admin)->get(route('admin.users.index'))),
        );
    }

    public function testInTheAdminAreaTheHouseLeadsToTheDashboard(): void
    {
        $trail = $this->trail($this->actingAs(User::factory()->create())->get(route('admin.pages.index')));

        $this->assertStringContainsString('href="' . route('admin.dashboard') . '"', $trail);
    }

    public function testTheHomePagesAndTheAdminFormsHaveNone(): void
    {
        $page = Page::factory()->create();
        $group = PageGroup::factory()->create();
        $admin = User::factory()->create();

        $this->assertSame('', $this->trail($this->get(route('home'))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.dashboard'))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.pages.create'))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.pages.edit', $page))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.page-groups.create'))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.page-groups.edit', $group))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.users.create'))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.users.edit', $admin))));
    }

    /**
     * The rendered breadcrumb list, or an empty string when the page has none.
     *
     * @param TestResponse<Response> $response
     */
    private function trail(TestResponse $response): string
    {
        preg_match('#<ol class="breadcrumb.*?</ol>#s', (string) $response->getContent(), $matches);

        return $matches[0] ?? '';
    }
}
