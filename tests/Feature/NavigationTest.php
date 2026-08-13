<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsSeeNeitherTheAccountMenuNorTheAdminArea(): void
    {
        $response = $this->get(route('home'));

        $response->assertDontSee(route('logout'));
        $response->assertDontSee(route('admin.dashboard'));
        $response->assertDontSee('admin-frame');
    }

    public function testThePublicSiteOffersAuthenticatedUsersAWayIntoTheAdminArea(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('home'));

        $response->assertSee('href="' . route('admin.dashboard') . '"', false);
        $response->assertSee(route('logout'));
        $response->assertDontSee('admin-frame');
        $response->assertDontSee(__('Admin area'));
    }

    public function testTheAdminAreaIsMarkedAndOffersAWayBackToThePublicSite(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard'));

        $response->assertSee('admin-frame');
        $response->assertSee(__('Admin area'));
        $response->assertSee(__('View site'));
        $response->assertSee(route('logout'));
    }

    public function testTheAdminMenuLinksToEveryAdminSection(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard'));

        $response->assertSee('href="' . route('admin.pages.index') . '"', false);
        $response->assertSee('href="' . route('admin.page-groups.index') . '"', false);
        $response->assertSee('href="' . route('admin.users.index') . '"', false);
    }
}
