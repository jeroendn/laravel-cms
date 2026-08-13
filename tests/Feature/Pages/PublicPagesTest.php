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
            'title' => 'Magnesium and sleep',
            'slug' => 'sleep',
            'body' => '<h2>Why magnesium?</h2><p>Because it works.</p>',
        ]);

        $response = $this->get('/sleep');

        $response->assertOk();
        $response->assertSee('Magnesium and sleep');
        $response->assertSee('<h2>Why magnesium?</h2>', false);
    }

    public function testAGroupedPageIsOnlyServedAtItsFullPath(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'health']);
        Page::factory()->visible()->create([
            'slug' => 'sleep',
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/health/sleep')->assertOk();
        $this->get('/sleep')->assertNotFound();
    }

    public function testASubgroupPageIsOnlyServedAtItsFullPath(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'health']);
        $subgroup = PageGroup::factory()->create(['slug' => 'minerals', 'parent_id' => $group->id]);
        Page::factory()->visible()->create([
            'slug' => 'magnesium',
            'page_group_id' => $subgroup->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/health/minerals/magnesium')->assertOk();
        $this->get('/health/magnesium')->assertNotFound();
        $this->get('/minerals/magnesium')->assertNotFound();
        $this->get('/magnesium')->assertNotFound();
    }

    public function testAWrongGroupPrefixDoesNotServeThePage(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'health']);
        PageGroup::factory()->create(['slug' => 'nutrition']);
        Page::factory()->visible()->create([
            'slug' => 'sleep',
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/nutrition/sleep')->assertNotFound();
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
        $group = PageGroup::factory()->create(['name' => 'Health', 'slug' => 'health']);
        $subgroup = PageGroup::factory()->create(['name' => 'Minerals', 'slug' => 'minerals', 'parent_id' => $group->id]);
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

        $response = $this->get('/health');

        $response->assertOk();
        $response->assertSee('Health');
        $response->assertSee('Visible page');
        $response->assertDontSee('Draft page');
        $response->assertDontSee('Scheduled page');
        // A subgroup's pages live behind the subgroup link, not in this list.
        $response->assertDontSee('Subgroup page');
        $response->assertSee($subgroup->url());
    }

    public function testASubgroupRendersItsOwnOverview(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'health']);
        $subgroup = PageGroup::factory()->create(['name' => 'Minerals', 'slug' => 'minerals', 'parent_id' => $group->id]);
        Page::factory()->visible()->create([
            'title' => 'Magnesium basics',
            'page_group_id' => $subgroup->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/health/minerals');

        $response->assertOk();
        $response->assertSee('Minerals');
        $response->assertSee('Magnesium basics');
    }

    public function testTheOverviewSortsNewestFirstAndPaginates(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'health']);
        Page::factory()->visible()->create([
            'title' => 'Oldest page',
            'page_group_id' => $group->id,
            'published_at' => now()->subDays(2),
        ]);
        Page::factory()->visible()->count(10)->create([
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/health');

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
