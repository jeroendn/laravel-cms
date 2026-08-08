<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostsTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsAreRedirectedToLogin(): void
    {
        $post = Post::factory()->create();

        $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
        $this->get(route('admin.posts.create'))->assertRedirect(route('login'));
        $this->post(route('admin.posts.store'))->assertRedirect(route('login'));
        $this->get(route('admin.posts.edit', $post))->assertRedirect(route('login'));
        $this->put(route('admin.posts.update', $post))->assertRedirect(route('login'));
        $this->delete(route('admin.posts.destroy', $post))->assertRedirect(route('login'));
    }

    public function testIndexListsAllPostsIncludingDrafts(): void
    {
        Post::factory()->published()->create(['title' => 'Published post']);
        Post::factory()->create(['title' => 'Draft post']);

        $response = $this->actingAs($this->admin())->get(route('admin.posts.index'));

        $response->assertOk();
        $response->assertSee('Published post');
        $response->assertSee('Draft post');
    }

    public function testAdminCanViewTheCreateAndEditForms(): void
    {
        $post = Post::factory()->create(['title' => 'Existing post']);
        $admin = $this->admin();

        $create = $this->actingAs($admin)->get(route('admin.posts.create'));
        $create->assertOk();
        $create->assertSee('body-editor');

        $edit = $this->actingAs($admin)->get(route('admin.posts.edit', $post));
        $edit->assertOk();
        $edit->assertSee('Existing post');
    }

    public function testAdminCanCreateDraftWithGeneratedSlug(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'title' => 'Magnesium and Sports',
            'slug' => '',
            'body' => '<p>Content</p>',
        ]);

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', [
            'title' => 'Magnesium and Sports',
            'slug' => 'magnesium-and-sports',
            'published_at' => null,
        ]);
    }

    public function testAdminCanCreatePublishedPost(): void
    {
        $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'title' => 'Instantly live',
            'slug' => '',
            'body' => '<p>Content</p>',
            'published' => '1',
        ]);

        $post = Post::query()->firstOrFail();

        $this->assertTrue($post->isPublished());
    }

    public function testTitleBodyAndUniqueSlugAreValidated(): void
    {
        Post::factory()->create(['slug' => 'existing-post']);

        $response = $this->actingAs($this->admin())->post(route('admin.posts.store'), [
            'title' => '',
            'slug' => 'existing-post',
            'body' => '',
        ]);

        $response->assertSessionHasErrors(['title', 'slug', 'body']);
    }

    public function testAdminCanUpdateAPostKeepingThePublicationDate(): void
    {
        $post = Post::factory()->published()->create();
        $originallyPublishedAt = $post->published_at;
        $this->assertNotNull($originallyPublishedAt);

        $response = $this->actingAs($this->admin())->put(route('admin.posts.update', $post), [
            'title' => 'New title',
            'slug' => $post->slug,
            'body' => '<p>New content</p>',
            'published' => '1',
        ]);

        $response->assertRedirect(route('admin.posts.index'));
        $post->refresh();
        $this->assertSame('New title', $post->title);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->published_at->equalTo($originallyPublishedAt));
    }

    public function testAdminCanUnpublishAPost(): void
    {
        $post = Post::factory()->published()->create();

        $this->actingAs($this->admin())->put(route('admin.posts.update', $post), [
            'title' => $post->title,
            'slug' => $post->slug,
            'body' => $post->body,
        ]);

        $this->assertNull($post->refresh()->published_at);
    }

    public function testAdminCanDeleteAPost(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.posts.destroy', $post));

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function testOnlyPublishedPostsGetALinkToTheirPublicPage(): void
    {
        $published = Post::factory()->published()->create();
        $draft = Post::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.posts.index'));

        $response->assertSee(route('posts.show', $published));
        $response->assertDontSee(route('posts.show', $draft));
    }

    private function admin(): User
    {
        return User::factory()->create();
    }
}
