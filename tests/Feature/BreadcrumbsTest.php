<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    public function testThePostPageShowsItsTitleBehindTheHomeIcon(): void
    {
        $post = Post::factory()->published()->create(['title' => 'A published post']);

        $response = $this->get(route('posts.show', $post));

        $response->assertSee('breadcrumb', escape: false);
        $response->assertSee('fa-house', escape: false);
        $response->assertSee('A published post');
    }

    public function testTheAdminOverviewHasBreadcrumbs(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.posts.index'));

        $response->assertSee('breadcrumb', escape: false);
    }

    public function testTheHomePageAndTheAdminFormsHaveNone(): void
    {
        $post = Post::factory()->create();
        $admin = User::factory()->create();

        $this->get(route('home'))->assertDontSee('breadcrumb', escape: false);
        $this->actingAs($admin)->get(route('admin.posts.create'))->assertDontSee('breadcrumb', escape: false);
        $this->actingAs($admin)->get(route('admin.posts.edit', $post))->assertDontSee('breadcrumb', escape: false);
    }
}
