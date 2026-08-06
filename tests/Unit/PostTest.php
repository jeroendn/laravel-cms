<?php

namespace Tests\Unit;

use App\Models\Post;
use Tests\TestCase;

/**
 * The pure model logic of Post: no database, no HTTP.
 */
class PostTest extends TestCase
{
    public function testExcerptStripsMarkupAndCollapsesWhitespace(): void
    {
        $post = new Post(['body' => "<p>Magnesium   <strong>helps</strong>\n\nwith sleep.</p>"]);

        $this->assertSame('Magnesium helps with sleep.', $post->excerpt());
    }

    public function testExcerptLeavesShortBodiesIntact(): void
    {
        $post = new Post(['body' => '<p>Short and sweet.</p>']);

        $this->assertSame('Short and sweet.', $post->excerpt());
    }

    public function testExcerptTruncatesAtTheGivenLength(): void
    {
        $post = new Post(['body' => '<p>' . str_repeat('a', 300) . '</p>']);

        $this->assertSame(str_repeat('a', 50) . '...', $post->excerpt(50));
    }

    public function testExcerptIsBuiltFromTheSanitizedBody(): void
    {
        $post = new Post(['body' => '<p>Safe content</p><script>alert(1)</script>']);

        $this->assertSame('Safe content', $post->excerpt());
    }

    public function testIsPublishedIsFalseForADraft(): void
    {
        $post = new Post(['published_at' => null]);

        $this->assertFalse($post->isPublished());
    }

    public function testIsPublishedIsFalseForAScheduledPost(): void
    {
        $post = new Post(['published_at' => now()->addMinute()]);

        $this->assertFalse($post->isPublished());
    }

    public function testIsPublishedIsTrueAtTheExactPublicationMoment(): void
    {
        $this->freezeTime();

        $post = new Post(['published_at' => now()]);

        $this->assertTrue($post->isPublished());
    }
}
