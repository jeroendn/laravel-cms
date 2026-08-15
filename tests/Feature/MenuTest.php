<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function testTheMenuShowsOnlyMenuToggledVisibleItems(): void
    {
        Page::factory()->visible()->create(['title' => 'In the menu', 'show_in_menu' => true]);
        Page::factory()->visible()->create(['title' => 'Not toggled']);
        Page::factory()->create(['title' => 'Draft page', 'show_in_menu' => true]);
        PageGroup::factory()->create(['name' => 'Menu group', 'show_in_menu' => true]);
        $hidden = PageGroup::factory()->create(['name' => 'Hidden group']);
        Page::factory()->visible()->create([
            'title' => 'Orphan page',
            'show_in_menu' => true,
            'page_group_id' => $hidden->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/');

        $response->assertSee('In the menu');
        $response->assertDontSee('Not toggled');
        $response->assertDontSee('Draft page');
        $response->assertSee('Menu group');
        $response->assertDontSee('Hidden group');
        // Its group is not in the menu, so the page has no dropdown to sit in.
        $response->assertDontSee('Orphan page');
    }

    public function testMenuItemsSortByPriorityThenAlphabetically(): void
    {
        Page::factory()->visible()->create(['title' => 'Despair', 'show_in_menu' => true]);
        Page::factory()->visible()->create(['title' => 'death', 'show_in_menu' => true]);
        Page::factory()->visible()->create(['title' => 'Destiny', 'show_in_menu' => true, 'priority' => 5]);

        $response = $this->get('/');

        $response->assertSeeInOrder(['Destiny', 'death', 'Despair']);
    }

    public function testAGroupDropdownSortsItsPagesAndEndsWithShowAll(): void
    {
        app()->setLocale('en');
        $group = PageGroup::factory()->create(['name' => 'The Endless', 'slug' => 'the-endless', 'show_in_menu' => true]);
        Page::factory()->visible()->create([
            'title' => 'A Game of You',
            'show_in_menu' => true,
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);
        Page::factory()->visible()->create([
            'title' => 'The Wake',
            'show_in_menu' => true,
            'priority' => 9,
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/');

        $response->assertSeeInOrder(['The Endless', 'The Wake', 'A Game of You', 'Show All']);
        $response->assertSee($group->url());
    }

    public function testAnEmptyMenuGroupStillOffersShowAll(): void
    {
        app()->setLocale('en');
        PageGroup::factory()->create(['name' => 'Empty group', 'show_in_menu' => true]);

        $response = $this->get('/');

        $response->assertSeeInOrder(['Empty group', 'Show All']);
    }

    public function testASubgroupBecomesAFlyoutSubmenu(): void
    {
        app()->setLocale('en');
        $group = PageGroup::factory()->create(['name' => 'The Endless', 'slug' => 'the-endless', 'show_in_menu' => true]);
        $subgroup = PageGroup::factory()->create([
            'name' => 'Dream',
            'slug' => 'dream',
            'parent_id' => $group->id,
            'show_in_menu' => true,
        ]);
        Page::factory()->visible()->create([
            'title' => 'Preludes and Nocturnes',
            'show_in_menu' => true,
            'page_group_id' => $subgroup->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/');

        $response->assertSee('data-submenu', false);
        $response->assertSeeInOrder(['Dream', 'Preludes and Nocturnes', 'Show All']);
        $response->assertSee($subgroup->url());
    }

    public function testAHiddenSubgroupKeepsItsPagesOutOfTheMenu(): void
    {
        $group = PageGroup::factory()->create(['name' => 'The Endless', 'show_in_menu' => true]);
        $subgroup = PageGroup::factory()->create(['name' => 'Hidden subgroup', 'parent_id' => $group->id]);
        Page::factory()->visible()->create([
            'title' => 'Tucked away',
            'show_in_menu' => true,
            'page_group_id' => $subgroup->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/');

        $response->assertDontSee('Hidden subgroup');
        $response->assertDontSee('Tucked away');
    }

    public function testTheHomePageAppearsViaItsOwnToggle(): void
    {
        Page::factory()->visible()->create([
            'title' => 'Welcome to the Dreaming',
            'slug' => 'home',
            'show_in_menu' => true,
        ]);
        $other = Page::factory()->visible()->create();

        $response = $this->get($other->url());

        $response->assertSee('Welcome to the Dreaming');
        $response->assertSee('href="' . route('home') . '"', false);
    }

    public function testAHomePageWithoutTheToggleAddsNoMenuItem(): void
    {
        Page::factory()->visible()->create(['title' => 'Welcome to the Dreaming', 'slug' => 'home']);
        $other = Page::factory()->visible()->create();

        $response = $this->get($other->url());

        $response->assertDontSee('Welcome to the Dreaming');
    }

    public function testAMenuHiddenPageStaysReachable(): void
    {
        $page = Page::factory()->visible()->create();

        $this->get($page->url())->assertOk();
    }

    public function testTheCurrentPageIsMarkedActive(): void
    {
        $page = Page::factory()->visible()->create(['show_in_menu' => true]);

        $response = $this->get($page->url());

        $response->assertSee('nav-item active', false);
    }

    public function testAGroupIsMarkedActiveAnywhereInsideIt(): void
    {
        $group = PageGroup::factory()->create(['slug' => 'health', 'show_in_menu' => true]);
        $page = Page::factory()->visible()->create([
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get($page->url());

        $response->assertSee('nav-item dropdown active', false);
    }
}
