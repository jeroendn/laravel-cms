<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function testHomePageRenders(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('home');
        $response->assertSee(config()->string('app.name'));
    }

    public function testGuestDoesNotSeeLoginLinkOrLogoutButton(): void
    {
        $response = $this->get(route('home'));

        $response->assertDontSee(route('login'));
        $response->assertDontSee(route('logout'));
    }

    public function testAuthenticatedUserSeesLogoutButton(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertSee(route('logout'));
    }
}
