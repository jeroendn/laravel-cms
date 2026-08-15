<?php

namespace Tests\Feature\Pages;

use App\Models\Page;
use App\Models\PageGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function testAnUngroupedPageIsServedAtItsRootSlug(): void
    {
        Page::factory()->visible()->create([
            'title' => 'Dream Country',
            'slug' => 'dream-country',
            'body' => '<h2>Why dreams?</h2><p>They shape the waking world.</p>',
        ]);

        $response = $this->get('/dream-country');

        $response->assertOk();
        $response->assertSee('Dream Country');
        $response->assertSee('<h2>Why dreams?</h2>', false);
    }

    public function testAGroupedPageIsOnlyServedAtItsFullPath(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'the-dreaming']);
        Page::factory()->visible()->create([
            'slug' => 'lucien',
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/the-dreaming/lucien')->assertOk();
        $this->get('/lucien')->assertNotFound();
    }

    public function testASubgroupPageIsOnlyServedAtItsFullPath(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'the-endless']);
        $subgroup = PageGroup::factory()->create(['slug' => 'dream', 'parent_id' => $group->id]);
        Page::factory()->visible()->create([
            'slug' => 'morpheus',
            'page_group_id' => $subgroup->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/the-endless/dream/morpheus')->assertOk();
        $this->get('/the-endless/morpheus')->assertNotFound();
        $this->get('/dream/morpheus')->assertNotFound();
        $this->get('/morpheus')->assertNotFound();
    }

    public function testAWrongGroupPrefixDoesNotServeThePage(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'the-dreaming']);
        PageGroup::factory()->create(['slug' => 'the-waking-world']);
        Page::factory()->visible()->create([
            'slug' => 'lucien',
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/the-waking-world/lucien')->assertNotFound();
    }

    public function testUnknownPathsAre404(): void
    {
        $this->get('/nope')->assertNotFound();
        $this->get('/nope/nope')->assertNotFound();
        $this->get('/nope/nope/nope')->assertNotFound();
        $this->get('/nope/nope/nope/nope')->assertNotFound();
    }

    public function testARootGroupRendersAnOverviewOfItsOwnPages(): void
    {
        $group = PageGroup::factory()->create(['name' => 'The Dreaming', 'slug' => 'the-dreaming']);
        $subgroup = PageGroup::factory()->create(['name' => 'Nightmares', 'slug' => 'nightmares', 'parent_id' => $group->id]);
        Page::factory()->visible()->create([
            'title' => 'Visible page',
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);
        Page::factory()->create(['title' => 'Draft page', 'page_group_id' => $group->id]);
        Page::factory()->visible()->create([
            'title' => 'Scheduled page',
            'page_group_id' => $group->id,
            'published_at' => now()->addDay(),
        ]);
        Page::factory()->visible()->create([
            'title' => 'Subgroup page',
            'page_group_id' => $subgroup->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/the-dreaming');

        $response->assertOk();
        $response->assertSee('The Dreaming');
        $response->assertSee('Visible page');
        $response->assertDontSee('Draft page');
        $response->assertDontSee('Scheduled page');
        // A subgroup's pages live behind the subgroup link, not in this list.
        $response->assertDontSee('Subgroup page');
        $response->assertSee($subgroup->url());
    }

    public function testASubgroupRendersItsOwnOverview(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'the-endless']);
        $subgroup = PageGroup::factory()->create(['name' => 'Dream', 'slug' => 'dream', 'parent_id' => $group->id]);
        Page::factory()->visible()->create([
            'title' => 'Preludes and Nocturnes',
            'page_group_id' => $subgroup->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/the-endless/dream');

        $response->assertOk();
        $response->assertSee('Dream');
        $response->assertSee('Preludes and Nocturnes');
    }

    public function testTheOverviewSortsNewestFirstAndPaginates(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'the-dreaming']);
        Page::factory()->visible()->create([
            'title' => 'Oldest page',
            'page_group_id' => $group->id,
            'published_at' => now()->subDays(2),
        ]);
        Page::factory()->visible()->count(10)->create([
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/the-dreaming');

        $this->assertSame(10, substr_count((string) $response->getContent(), 'class="card mb-3"'));
        $response->assertDontSee('Oldest page');
        $response->assertSee(__('Older pages'));
    }

    public function testADraftPageIsNotPubliclyVisible(): void
    {
        $page = Page::factory()->create();

        $this->get($page->url())->assertNotFound();
    }

    public function testAScheduledGroupedPageIsNotPubliclyVisibleYet(): void
    {
        $group = PageGroup::factory()->create();
        $page = Page::factory()->visible()->create([
            'page_group_id' => $group->id,
            'published_at' => now()->addDay(),
        ]);

        $this->get($page->url())->assertNotFound();
    }

    public function testAGroupedPageBecomesVisibleOnItsPublicationDate(): void
    {
        $group = PageGroup::factory()->create();
        $page = Page::factory()->visible()->create([
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get($page->url())->assertOk();
    }

    public function testUnsafeHtmlIsStrippedFromTheBody(): void
    {
        $page = Page::factory()->visible()->create([
            'body' => '<p>Safe content</p><script>alert(1)</script>',
        ]);

        $response = $this->get($page->url());

        $response->assertOk();
        $response->assertSee('Safe content');
        $response->assertDontSee('alert(1)', false);
    }
}
