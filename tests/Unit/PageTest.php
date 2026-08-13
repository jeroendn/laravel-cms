<?php

namespace Tests\Unit;

use App\Models\Page;
use Tests\TestCase;

/**
 * The pure model logic of Page: no database, no HTTP.
 */
class PageTest extends TestCase
{
    public function testExcerptStripsMarkupAndCollapsesWhitespace(): void
    {
        $page = new Page(['body' => "<p>Magnesium   <strong>helps</strong>\n\nwith sleep.</p>"]);

        $this->assertSame('Magnesium helps with sleep.', $page->excerpt());
    }

    public function testExcerptLeavesShortBodiesIntact(): void
    {
        $page = new Page(['body' => '<p>Short and sweet.</p>']);

        $this->assertSame('Short and sweet.', $page->excerpt());
    }

    public function testExcerptTruncatesAtTheGivenLength(): void
    {
        $page = new Page(['body' => '<p>' . str_repeat('a', 300) . '</p>']);

        $this->assertSame(str_repeat('a', 50) . '...', $page->excerpt(50));
    }

    public function testExcerptIsBuiltFromTheSanitizedBody(): void
    {
        $page = new Page(['body' => '<p>Safe content</p><script>alert(1)</script>']);

        $this->assertSame('Safe content', $page->excerpt());
    }

    public function testIsPublishedIsFalseForADraft(): void
    {
        $page = new Page(['published_at' => null]);

        $this->assertFalse($page->isPublished());
    }

    public function testIsPublishedIsFalseForAScheduledPage(): void
    {
        $page = new Page(['published_at' => now()->addMinute()]);

        $this->assertFalse($page->isPublished());
    }

    public function testIsPublishedIsTrueAtTheExactPublicationMoment(): void
    {
        $this->freezeTime();

        $page = new Page(['published_at' => now()]);

        $this->assertTrue($page->isPublished());
    }
}
