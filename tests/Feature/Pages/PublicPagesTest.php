<?php

namespace Tests\Feature\Pages;

use App\Models\Page;
use App\Models\PageGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function testHomePageListsOnlyVisiblePages(): void
    {
        $visible = Page::factory()->visible()->create(['title' => 'Magnesium and sleep']);
        Page::factory()->create(['title' => 'Unfinished draft']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Magnesium and sleep');
        $response->assertSee(route('pages.show', $visible));
        $response->assertDontSee('Unfinished draft');
    }

    public function testHomePageShowsNewestPageFirst(): void
    {
        Page::factory()->visible()->create([
            'title' => 'Oldest page',
            'published_at' => now()->subDays(2),
        ]);
        Page::factory()->visible()->create([
            'title' => 'Newest page',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('home'));

        $response->assertSeeInOrder(['Newest page', 'Oldest page']);
    }

    public function testHomePageTeasesOnlyTheMostRecentPagesAndLinksToTheArchive(): void
    {
        Page::factory()->visible()->count(6)->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertSame(5, substr_count((string) $response->getContent(), 'class="card mb-3"'));
        $response->assertSee(route('pages.index'));
    }

    public function testTheArchiveListsEveryVisiblePageNewestFirst(): void
    {
        $group = PageGroup::factory()->create();
        Page::factory()->visible()->create([
            'title' => 'Oldest page',
            'published_at' => now()->subDays(2),
        ]);
        Page::factory()->visible()->create([
            'title' => 'Newest page',
            'published_at' => now()->subDay(),
        ]);
        Page::factory()->create(['title' => 'Unfinished draft']);
        Page::factory()->visible()->create([
            'title' => 'Scheduled page',
            'page_group_id' => $group->id,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get(route('pages.index'));

        $response->assertOk();
        $response->assertViewIs('pages.index');
        $response->assertSeeInOrder(['Newest page', 'Oldest page']);
        $response->assertDontSee('Unfinished draft');
        $response->assertDontSee('Scheduled page');
    }

    public function testTheArchiveIsPaginated(): void
    {
        Page::factory()->visible()->count(11)->create();

        $response = $this->get(route('pages.index'));

        $this->assertSame(10, substr_count((string) $response->getContent(), 'class="card mb-3"'));
        $response->assertSee(__('Older pages'));
    }

    public function testVisiblePageIsShownWithRenderedHtml(): void
    {
        $page = Page::factory()->visible()->create([
            'title' => 'Magnesium and sleep',
            'body' => '<h2>Why magnesium?</h2><p>Because it works.</p>',
        ]);

        $response = $this->get(route('pages.show', $page));

        $response->assertOk();
        $response->assertSee('Magnesium and sleep');
        $response->assertSee('<h2>Why magnesium?</h2>', false);
    }

    public function testUnsafeHtmlIsStrippedFromTheBody(): void
    {
        $page = Page::factory()->visible()->create([
            'body' => '<p>Safe content</p><script>alert(1)</script>',
        ]);

        $response = $this->get(route('pages.show', $page));

        $response->assertOk();
        $response->assertSee('Safe content');
        $response->assertDontSee('alert(1)', false);
    }

    public function testDraftPageIsNotPubliclyVisible(): void
    {
        $page = Page::factory()->create();

        $this->get(route('pages.show', $page))->assertNotFound();
    }

    public function testScheduledGroupedPageIsNotPubliclyVisibleYet(): void
    {
        $group = PageGroup::factory()->create();
        $page = Page::factory()->visible()->create([
            'page_group_id' => $group->id,
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('pages.show', $page))->assertNotFound();
    }

    public function testAGroupedPageBecomesVisibleOnItsPublicationDate(): void
    {
        $group = PageGroup::factory()->create();
        $page = Page::factory()->visible()->create([
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('pages.show', $page))->assertOk();
    }
}
