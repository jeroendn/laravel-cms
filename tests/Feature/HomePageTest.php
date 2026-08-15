<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function testHomeIsABareLayoutWithoutAHomePage(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('home');
        $response->assertSee(config()->string('app.name'));
    }

    public function testHomeRendersTheVisiblePageSluggedHome(): void
    {
        Page::factory()->visible()->create([
            'title' => 'Welcome to the Dreaming',
            'slug' => 'home',
            'body' => '<p>All dreams begin here.</p>',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Welcome to the Dreaming');
        $response->assertSee('All dreams begin here.');
    }

    public function testADraftHomePageLeavesHomeBare(): void
    {
        Page::factory()->create(['title' => 'Almost ready', 'slug' => 'home']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Almost ready');
    }

    public function testTheHomeSlugRedirectsToTheRoot(): void
    {
        $response = $this->get('/home');

        $response->assertMovedPermanently();
        $response->assertRedirect('/');
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
