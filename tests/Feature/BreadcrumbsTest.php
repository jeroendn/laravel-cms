<?php

namespace Tests\Feature;

use Symfony\Component\HttpFoundation\Response;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    public function testThePostPageSitsBehindTheHomeIconAndTheArchive(): void
    {
        $post = Post::factory()->published()->create(['title' => 'A published post']);

        $trail = $this->trail($this->get(route('posts.show', $post)));

        $this->assertStringContainsString('fa-house', $trail);
        $this->assertStringContainsString(route('posts.index'), $trail);
        $this->assertStringContainsString('A published post', $trail);
    }

    public function testTheArchiveAndTheAdminOverviewsHaveBreadcrumbs(): void
    {
        $this->assertStringContainsString('fa-house', $this->trail($this->get(route('posts.index'))));

        $admin = User::factory()->create();
        $this->assertStringContainsString(
            'fa-house',
            $this->trail($this->actingAs($admin)->get(route('admin.posts.index'))),
        );
        $this->assertStringContainsString(
            'fa-house',
            $this->trail($this->actingAs($admin)->get(route('admin.users.index'))),
        );
    }

    public function testInTheAdminAreaTheHouseLeadsToTheDashboard(): void
    {
        $trail = $this->trail($this->actingAs(User::factory()->create())->get(route('admin.posts.index')));

        $this->assertStringContainsString('href="' . route('admin.dashboard') . '"', $trail);
    }

    public function testTheHomePagesAndTheAdminFormsHaveNone(): void
    {
        $post = Post::factory()->create();
        $admin = User::factory()->create();

        $this->assertSame('', $this->trail($this->get(route('home'))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.dashboard'))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.posts.create'))));
        $this->assertSame('', $this->trail($this->actingAs($admin)->get(route('admin.posts.edit', $post))));
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
