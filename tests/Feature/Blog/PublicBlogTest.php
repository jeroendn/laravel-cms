<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBlogTest extends TestCase
{
    use RefreshDatabase;

    public function testHomePageListsOnlyPublishedPosts(): void
    {
        $published = Post::factory()->published()->create(['title' => 'Magnesium and sleep']);
        Post::factory()->create(['title' => 'Unfinished draft']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Magnesium and sleep');
        $response->assertSee(route('posts.show', $published));
        $response->assertDontSee('Unfinished draft');
    }

    public function testHomePageShowsNewestPostFirst(): void
    {
        Post::factory()->published()->create([
            'title' => 'Oldest post',
            'published_at' => now()->subDays(2),
        ]);
        Post::factory()->published()->create([
            'title' => 'Newest post',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('home'));

        $response->assertSeeInOrder(['Newest post', 'Oldest post']);
    }

    public function testPublishedPostIsShownWithRenderedHtml(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Magnesium and sleep',
            'body' => '<h2>Why magnesium?</h2><p>Because it works.</p>',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee('Magnesium and sleep');
        $response->assertSee('<h2>Why magnesium?</h2>', false);
    }

    public function testUnsafeHtmlIsStrippedFromTheBody(): void
    {
        $post = Post::factory()->published()->create([
            'body' => '<p>Safe content</p><script>alert(1)</script>',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee('Safe content');
        $response->assertDontSee('alert(1)', false);
    }

    public function testDraftPostIsNotPubliclyVisible(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function testScheduledPostIsNotPubliclyVisibleYet(): void
    {
        $post = Post::factory()->create(['published_at' => now()->addDay()]);

        $this->get(route('posts.show', $post))->assertNotFound();
    }
}
