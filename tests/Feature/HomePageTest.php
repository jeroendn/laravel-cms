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
            'title' => 'Welcome to Magnesium',
            'slug' => 'home',
            'body' => '<p>Feel better.</p>',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Welcome to Magnesium');
        $response->assertSee('Feel better.');
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
