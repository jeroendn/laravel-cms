<?php

namespace Tests\Feature\Pages;

use App\Models\Page;
use App\Models\PageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsAreRedirectedToLogin(): void
    {
        $page = Page::factory()->create();

        $this->get(route('admin.pages.index'))->assertRedirect(route('login'));
        $this->get(route('admin.pages.create'))->assertRedirect(route('login'));
        $this->post(route('admin.pages.store'))->assertRedirect(route('login'));
        $this->get(route('admin.pages.edit', $page))->assertRedirect(route('login'));
        $this->put(route('admin.pages.update', $page))->assertRedirect(route('login'));
        $this->delete(route('admin.pages.destroy', $page))->assertRedirect(route('login'));
    }

    public function testIndexListsAllPagesWithTheirGroup(): void
    {
        $group = PageGroup::factory()->create(['name' => 'Health']);
        Page::factory()->visible()->create(['title' => 'Published page']);
        Page::factory()->create(['title' => 'Draft page', 'page_group_id' => $group->id]);

        $response = $this->actingAs($this->admin())->get(route('admin.pages.index'));

        $response->assertOk();
        $response->assertSee('Published page');
        $response->assertSee('Draft page');
        $response->assertSee('Health');
    }

    public function testAdminCanViewTheCreateAndEditForms(): void
    {
        $parent = PageGroup::factory()->create(['name' => 'Health']);
        PageGroup::factory()->create(['name' => 'Sleep', 'parent_id' => $parent->id]);
        $page = Page::factory()->create(['title' => 'Existing page']);
        $admin = $this->admin();

        $create = $this->actingAs($admin)->get(route('admin.pages.create'));
        $create->assertOk();
        $create->assertSee('body-editor');
        // The group select labels a subgroup with its parent's name.
        $create->assertSee('Health / Sleep');
        // A new page starts as a draft: the toggle is checked by default.
        $this->assertMatchesRegularExpression('/name="is_draft" value="1"\s+checked/', (string) $create->getContent());

        $edit = $this->actingAs($admin)->get(route('admin.pages.edit', $page));
        $edit->assertOk();
        $edit->assertSee('Existing page');
    }

    public function testTheFormCarriesTheDataForTheLiveUrlPreview(): void
    {
        $parent = PageGroup::factory()->create(['slug' => 'health']);
        PageGroup::factory()->create(['slug' => 'sleep', 'parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin())->get(route('admin.pages.create'));

        $response->assertSee('id="url-preview"', false);
        $response->assertSee('data-base="' . url('/') . '"', false);
        $response->assertSee('data-home-slug="home"', false);
        // A grouped page's URL repeats its group's full path, subgroups included.
        $response->assertSee('data-path="health/sleep"', false);
    }

    public function testAdminCanCreateDraftWithGeneratedSlug(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Magnesium and Sports',
            'slug' => '',
            'body' => '<p>Content</p>',
            'is_draft' => '1',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('pages', [
            'title' => 'Magnesium and Sports',
            'slug' => 'magnesium-and-sports',
            'is_draft' => true,
            'show_in_menu' => false,
            'priority' => 0,
            'page_group_id' => null,
            'published_at' => null,
        ]);
    }

    public function testFlashStatusRendersAsAnAutoHidingToast(): void
    {
        $response = $this->actingAs($this->admin())->followingRedirects()->post(route('admin.pages.store'), [
            'title' => 'Toasted',
            'slug' => '',
            'body' => '<p>Content</p>',
        ]);

        $response->assertSee(__(':Name created.', ['name' => __('page')]));
        $response->assertSee('toast-progress', false);
        $response->assertSee('data-bs-autohide="false"', false);
    }

    public function testAnUngroupedPageIsVisibleWithoutAPublicationDate(): void
    {
        // No is_draft in the payload: an unchecked toggle publishes the page.
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Instantly live',
            'slug' => '',
            'body' => '<p>Content</p>',
        ]);

        $page = Page::query()->firstOrFail();

        $this->assertTrue($page->isVisible());
        $this->assertNull($page->published_at);
    }

    public function testAnUngroupedPageIgnoresTheSubmittedPublicationDate(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Dateless',
            'slug' => '',
            'body' => '<p>Content</p>',
            'published_at' => now()->addWeek()->format('Y-m-d'),
        ]);

        $page = Page::query()->firstOrFail();

        $this->assertNull($page->published_at);
        $this->assertTrue($page->isVisible());
    }

    public function testAdminCanCreateAPageInAGroup(): void
    {
        $group = PageGroup::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Grouped',
            'slug' => '',
            'body' => '<p>Content</p>',
            'page_group_id' => (string) $group->id,
            'published_at' => now()->format('Y-m-d'),
            'show_in_menu' => '1',
            'priority' => '5',
        ]);

        $this->assertDatabaseHas('pages', [
            'title' => 'Grouped',
            'page_group_id' => $group->id,
            'is_draft' => false,
            'show_in_menu' => true,
            'priority' => 5,
        ]);
    }

    public function testAGroupedPublishedPageRequiresAPublicationDate(): void
    {
        $group = PageGroup::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'No date',
            'slug' => '',
            'body' => '<p>Content</p>',
            'page_group_id' => (string) $group->id,
        ]);

        $response->assertSessionHasErrors('published_at');
    }

    public function testAGroupedDraftNeedsNoPublicationDate(): void
    {
        $group = PageGroup::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Grouped draft',
            'slug' => '',
            'body' => '<p>Content</p>',
            'page_group_id' => (string) $group->id,
            'is_draft' => '1',
        ]);

        $this->assertDatabaseHas('pages', [
            'title' => 'Grouped draft',
            'is_draft' => true,
            'published_at' => null,
        ]);
    }

    public function testAdminCanScheduleAGroupedPage(): void
    {
        $group = PageGroup::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Coming up',
            'slug' => '',
            'body' => '<p>Content</p>',
            'page_group_id' => (string) $group->id,
            'published_at' => now()->addWeek()->format('Y-m-d'),
        ]);

        $page = Page::query()->firstOrFail();

        $this->assertTrue($page->isScheduled());
        $this->assertFalse($page->isVisible());
    }

    public function testStatusBadgesDistinguishPublishedScheduledAndDraft(): void
    {
        app()->setLocale('en');
        $group = PageGroup::factory()->create();
        Page::factory()->visible()->create();
        Page::factory()->visible()->create([
            'page_group_id' => $group->id,
            'published_at' => now()->addWeek(),
        ]);
        Page::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.pages.index'));

        $response->assertSeeInOrder(['bg-green-lt', 'Published']);
        $response->assertSeeInOrder(['bg-yellow-lt', 'Scheduled']);
        $response->assertSeeInOrder(['bg-secondary-lt', 'Draft']);
    }

    public function testTitleBodyAndUniqueSlugAreValidated(): void
    {
        Page::factory()->create(['slug' => 'existing-page']);

        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => '',
            'slug' => 'existing-page',
            'body' => '',
        ]);

        $response->assertSessionHasErrors(['title', 'slug', 'body']);
    }

    public function testThePageSlugMayNotCollideWithAGroupSlug(): void
    {
        PageGroup::factory()->create(['slug' => 'health']);

        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Health',
            'slug' => 'health',
            'body' => '<p>Content</p>',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function testAnUngroupedPageMayNotUseAReservedSlug(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Admin',
            'slug' => 'admin',
            'body' => '<p>Content</p>',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function testAGroupedPageMayReuseAReservedSlug(): void
    {
        $group = PageGroup::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Admin',
            'slug' => 'admin',
            'body' => '<p>Content</p>',
            'page_group_id' => (string) $group->id,
            'is_draft' => '1',
        ]);

        $response->assertSessionDoesntHaveErrors('slug');
        $this->assertDatabaseHas('pages', ['slug' => 'admin', 'page_group_id' => $group->id]);
    }

    public function testTheHomeSlugIsAllowedForPages(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Home',
            'slug' => 'home',
            'body' => '<p>Content</p>',
        ]);

        $this->assertDatabaseHas('pages', ['slug' => 'home']);
    }

    public function testAdminCanUpdateAPageKeepingThePublicationDate(): void
    {
        $group = PageGroup::factory()->create();
        // Day precision: the form's date field carries no time.
        $page = Page::factory()->visible()->create([
            'page_group_id' => $group->id,
            'published_at' => now()->subDay()->startOfDay(),
        ]);
        $originallyPublishedAt = $page->published_at;
        $this->assertNotNull($originallyPublishedAt);

        $response = $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => 'New title',
            'slug' => $page->slug,
            'body' => '<p>New content</p>',
            'page_group_id' => (string) $group->id,
            'published_at' => $originallyPublishedAt->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $page->refresh();
        $this->assertSame('New title', $page->title);
        $this->assertNotNull($page->published_at);
        $this->assertTrue($page->published_at->equalTo($originallyPublishedAt));
    }

    public function testAdminCanRevertAPageToDraft(): void
    {
        $page = Page::factory()->visible()->create();

        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'slug' => $page->slug,
            'body' => $page->body,
            'is_draft' => '1',
        ]);

        $this->assertTrue($page->refresh()->is_draft);
    }

    public function testRemovingTheGroupClearsThePublicationDate(): void
    {
        $group = PageGroup::factory()->create();
        $page = Page::factory()->visible()->create([
            'page_group_id' => $group->id,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'slug' => $page->slug,
            'body' => $page->body,
            'page_group_id' => '',
            'published_at' => now()->subDay()->format('Y-m-d'),
        ]);

        $page->refresh();
        $this->assertNull($page->page_group_id);
        $this->assertNull($page->published_at);
    }

    public function testAdminCanDeleteAPage(): void
    {
        $page = Page::factory()->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.pages.destroy', $page));

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function testOnlyVisiblePagesGetALinkToTheirPublicPage(): void
    {
        $visible = Page::factory()->visible()->create();
        $draft = Page::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.pages.index'));

        $response->assertSee($visible->url());
        $response->assertDontSee($draft->url());
    }

    private function admin(): User
    {
        return User::factory()->create();
    }
}
