<?php

namespace Tests\Feature\Pages;

use App\Models\Page;
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

    public function testIndexListsAllPagesIncludingDrafts(): void
    {
        Page::factory()->published()->create(['title' => 'Published page']);
        Page::factory()->create(['title' => 'Draft page']);

        $response = $this->actingAs($this->admin())->get(route('admin.pages.index'));

        $response->assertOk();
        $response->assertSee('Published page');
        $response->assertSee('Draft page');
    }

    public function testAdminCanViewTheCreateAndEditForms(): void
    {
        $page = Page::factory()->create(['title' => 'Existing page']);
        $admin = $this->admin();

        $create = $this->actingAs($admin)->get(route('admin.pages.create'));
        $create->assertOk();
        $create->assertSee('body-editor');

        $edit = $this->actingAs($admin)->get(route('admin.pages.edit', $page));
        $edit->assertOk();
        $edit->assertSee('Existing page');
    }

    public function testAdminCanCreateDraftWithGeneratedSlug(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Magnesium and Sports',
            'slug' => '',
            'body' => '<p>Content</p>',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('pages', [
            'title' => 'Magnesium and Sports',
            'slug' => 'magnesium-and-sports',
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

    public function testAdminCanCreatePublishedPage(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Instantly live',
            'slug' => '',
            'body' => '<p>Content</p>',
            'published_at' => now()->format('Y-m-d'),
        ]);

        $page = Page::query()->firstOrFail();

        $this->assertTrue($page->isPublished());
    }

    public function testAdminCanScheduleAPage(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Coming up',
            'slug' => '',
            'body' => '<p>Content</p>',
            'published_at' => now()->addWeek()->format('Y-m-d'),
        ]);

        $page = Page::query()->firstOrFail();

        $this->assertTrue($page->isScheduled());
        $this->assertFalse($page->isPublished());
    }

    public function testStatusBadgesDistinguishPublishedScheduledAndDraft(): void
    {
        app()->setLocale('en');
        Page::factory()->published()->create();
        Page::factory()->create(['published_at' => now()->addWeek()]);
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

    public function testAdminCanUpdateAPageKeepingThePublicationDate(): void
    {
        // Day precision: the form's date field carries no time.
        $page = Page::factory()->published()->create(['published_at' => now()->subDay()->startOfDay()]);
        $originallyPublishedAt = $page->published_at;
        $this->assertNotNull($originallyPublishedAt);

        $response = $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => 'New title',
            'slug' => $page->slug,
            'body' => '<p>New content</p>',
            'published_at' => $originallyPublishedAt->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $page->refresh();
        $this->assertSame('New title', $page->title);
        $this->assertNotNull($page->published_at);
        $this->assertTrue($page->published_at->equalTo($originallyPublishedAt));
    }

    public function testAdminCanUnpublishAPage(): void
    {
        $page = Page::factory()->published()->create();

        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'slug' => $page->slug,
            'body' => $page->body,
            'published_at' => '',
        ]);

        $this->assertNull($page->refresh()->published_at);
    }

    public function testAdminCanDeleteAPage(): void
    {
        $page = Page::factory()->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.pages.destroy', $page));

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function testOnlyPublishedPagesGetALinkToTheirPublicPage(): void
    {
        $published = Page::factory()->published()->create();
        $draft = Page::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.pages.index'));

        $response->assertSee(route('pages.show', $published));
        $response->assertDontSee(route('pages.show', $draft));
    }

    private function admin(): User
    {
        return User::factory()->create();
    }
}
