<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsAreRedirectedToLogin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function testTheDashboardIsTheLandingPageOfTheAdminArea(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('Dashboard'));
        $response->assertSee(__('Admin area'));
    }
}
