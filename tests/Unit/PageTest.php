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

    public function testADraftIsNeverVisible(): void
    {
        $page = new Page(['is_draft' => true]);

        $this->assertFalse($page->isVisible());
    }

    public function testAnUngroupedPublishedPageIsVisibleWithoutADate(): void
    {
        $page = new Page(['is_draft' => false]);

        $this->assertTrue($page->isVisible());
    }

    public function testAnUngroupedPageIgnoresAFutureDate(): void
    {
        $page = new Page(['is_draft' => false, 'published_at' => now()->addDay()]);

        $this->assertTrue($page->isVisible());
        $this->assertFalse($page->isScheduled());
    }

    public function testAGroupedPageWithoutADateIsNotVisible(): void
    {
        $page = new Page(['is_draft' => false, 'page_group_id' => 1]);

        $this->assertFalse($page->isVisible());
        $this->assertFalse($page->isScheduled());
    }

    public function testAGroupedPageWithAFutureDateIsScheduledNotVisible(): void
    {
        $page = new Page([
            'is_draft' => false,
            'page_group_id' => 1,
            'published_at' => now()->addMinute(),
        ]);

        $this->assertFalse($page->isVisible());
        $this->assertTrue($page->isScheduled());
    }

    public function testAGroupedPageIsVisibleAtTheExactPublicationMoment(): void
    {
        $this->freezeTime();

        $page = new Page([
            'is_draft' => false,
            'page_group_id' => 1,
            'published_at' => now(),
        ]);

        $this->assertTrue($page->isVisible());
        $this->assertFalse($page->isScheduled());
    }
}
