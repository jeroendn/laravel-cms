<?php

namespace Tests\Feature\Pages;

use App\Models\Page;
use App\Models\PageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPageGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsAreRedirectedToLogin(): void
    {
        $group = PageGroup::factory()->create();

        $this->get(route('admin.page-groups.index'))->assertRedirect(route('login'));
        $this->get(route('admin.page-groups.create'))->assertRedirect(route('login'));
        $this->post(route('admin.page-groups.store'))->assertRedirect(route('login'));
        $this->get(route('admin.page-groups.edit', $group))->assertRedirect(route('login'));
        $this->put(route('admin.page-groups.update', $group))->assertRedirect(route('login'));
        $this->delete(route('admin.page-groups.destroy', $group))->assertRedirect(route('login'));
    }

    public function testIndexListsGroupsWithTheirParent(): void
    {
        $parent = PageGroup::factory()->create(['name' => 'Health']);
        PageGroup::factory()->create(['name' => 'Sleep', 'parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin())->get(route('admin.page-groups.index'));

        $response->assertOk();
        // Sorted by name; the subgroup row repeats its parent's name.
        $response->assertSeeInOrder(['Health', 'Sleep', 'Health']);
    }

    public function testAdminCanViewTheCreateAndEditForms(): void
    {
        $group = PageGroup::factory()->create(['name' => 'Existing group']);
        $admin = $this->admin();

        $create = $this->actingAs($admin)->get(route('admin.page-groups.create'));
        $create->assertOk();
        $create->assertSee(__('Parent group'));

        $edit = $this->actingAs($admin)->get(route('admin.page-groups.edit', $group));
        $edit->assertOk();
        $edit->assertSee('Existing group');
    }

    public function testAdminCanCreateAGroupWithGeneratedSlug(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.page-groups.store'), [
            'name' => 'Magnesium and Health',
            'slug' => '',
        ]);

        $response->assertRedirect(route('admin.page-groups.index'));
        $this->assertDatabaseHas('page_groups', [
            'name' => 'Magnesium and Health',
            'slug' => 'magnesium-and-health',
            'show_in_menu' => false,
            'priority' => 0,
            'parent_id' => null,
        ]);
    }

    public function testAdminCanCreateAMenuSubgroupWithPriority(): void
    {
        $parent = PageGroup::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.page-groups.store'), [
            'name' => 'Subgroup',
            'slug' => '',
            'show_in_menu' => '1',
            'priority' => '5',
            'parent_id' => (string) $parent->id,
        ]);

        $this->assertDatabaseHas('page_groups', [
            'name' => 'Subgroup',
            'show_in_menu' => true,
            'priority' => 5,
            'parent_id' => $parent->id,
        ]);
    }

    public function testTheParentMustBeARootGroup(): void
    {
        $parent = PageGroup::factory()->create();
        $child = PageGroup::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin())->post(route('admin.page-groups.store'), [
            'name' => 'Too deep',
            'slug' => '',
            'parent_id' => (string) $child->id,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function testAGroupWithSubgroupsCannotGetAParent(): void
    {
        $group = PageGroup::factory()->create();
        PageGroup::factory()->create(['parent_id' => $group->id]);
        $other = PageGroup::factory()->create();

        $response = $this->actingAs($this->admin())->put(route('admin.page-groups.update', $group), [
            'name' => $group->name,
            'slug' => $group->slug,
            'parent_id' => (string) $other->id,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function testAGroupCannotBeItsOwnParent(): void
    {
        $group = PageGroup::factory()->create();

        $response = $this->actingAs($this->admin())->put(route('admin.page-groups.update', $group), [
            'name' => $group->name,
            'slug' => $group->slug,
            'parent_id' => (string) $group->id,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function testTheSlugMustBeUnique(): void
    {
        $parent = PageGroup::factory()->create();
        PageGroup::factory()->create(['slug' => 'tips', 'parent_id' => $parent->id]);

        // Also across scopes: this attempt sits at the root, the existing
        // slug in a group.
        $response = $this->actingAs($this->admin())->post(route('admin.page-groups.store'), [
            'name' => 'Tips',
            'slug' => 'tips',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function testTheSlugMayNotCollideWithAPageSlug(): void
    {
        Page::factory()->create(['slug' => 'tips']);

        $response = $this->actingAs($this->admin())->post(route('admin.page-groups.store'), [
            'name' => 'Tips',
            'slug' => 'tips',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function testARootGroupMayNotUseAReservedSlug(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.page-groups.store'), [
            'name' => 'Admin',
            'slug' => 'admin',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function testASubgroupMayReuseAReservedSlug(): void
    {
        $parent = PageGroup::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.page-groups.store'), [
            'name' => 'Admin',
            'slug' => 'admin',
            'parent_id' => (string) $parent->id,
        ]);

        $this->assertDatabaseHas('page_groups', ['slug' => 'admin', 'parent_id' => $parent->id]);
    }

    public function testAdminCanUpdateAGroup(): void
    {
        $group = PageGroup::factory()->create();

        $response = $this->actingAs($this->admin())->put(route('admin.page-groups.update', $group), [
            'name' => 'New name',
            'slug' => '',
            'show_in_menu' => '1',
            'priority' => '3',
        ]);

        $response->assertRedirect(route('admin.page-groups.index'));
        $group->refresh();
        $this->assertSame('New name', $group->name);
        $this->assertSame('new-name', $group->slug);
        $this->assertTrue($group->show_in_menu);
        $this->assertSame(3, $group->priority);
    }

    public function testAdminCanDeleteAnEmptyGroup(): void
    {
        $group = PageGroup::factory()->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.page-groups.destroy', $group));

        $response->assertRedirect(route('admin.page-groups.index'));
        $this->assertDatabaseMissing('page_groups', ['id' => $group->id]);
    }

    public function testAGroupWithSubgroupsCannotBeDeletedAndShowsAnErrorToast(): void
    {
        $group = PageGroup::factory()->create(['name' => 'Occupied']);
        PageGroup::factory()->create(['parent_id' => $group->id]);

        $response = $this->actingAs($this->admin())
            ->followingRedirects()
            ->delete(route('admin.page-groups.destroy', $group));

        $response->assertSee(__('Cannot delete :name: it still contains pages or subgroups.', ['name' => 'Occupied']));
        $response->assertSee('text-bg-danger');
        $this->assertDatabaseHas('page_groups', ['id' => $group->id]);
    }

    public function testAGroupWithPagesCannotBeDeleted(): void
    {
        $group = PageGroup::factory()->create(['name' => 'Filled']);
        Page::factory()->create(['page_group_id' => $group->id]);

        $response = $this->actingAs($this->admin())
            ->followingRedirects()
            ->delete(route('admin.page-groups.destroy', $group));

        $response->assertSee(__('Cannot delete :name: it still contains pages or subgroups.', ['name' => 'Filled']));
        $this->assertDatabaseHas('page_groups', ['id' => $group->id]);
    }

    public function testTheNameIsValidated(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.page-groups.store'), [
            'name' => '',
            'slug' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'slug']);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }
}
