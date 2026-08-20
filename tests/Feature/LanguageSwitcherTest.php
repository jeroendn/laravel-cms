<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitcherTest extends TestCase
{
    use RefreshDatabase;

    /** A single-language site has nothing to switch between. */
    public function testTheSwitcherIsHiddenWhileOneLanguageIsOffered(): void
    {
        Setting::current()->update(['locales' => ['nl'], 'default_locale' => 'nl']);

        $this->get(route('home'))->assertDontSee(route('language.switch', 'nl'));
    }

    public function testTheSwitcherListsEveryOfferedLanguage(): void
    {
        $this->offerBoth();

        $response = $this->get(route('home'));

        $response->assertSee(route('language.switch', 'nl'));
        $response->assertSee('English');
        $response->assertSee('Nederlands');
    }

    public function testSwitchingChangesTheLanguageAndSticks(): void
    {
        $this->offerBoth();

        $response = $this->from(route('home'))->post(route('language.switch', 'nl'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('locale', 'nl');

        $this->get(route('home'))->assertSee('Schakel navigatie');
    }

    public function testALanguageTheSiteDoesNotOfferIs404(): void
    {
        Setting::current()->update(['locales' => ['en'], 'default_locale' => 'en']);

        $this->post(route('language.switch', 'nl'))->assertNotFound();
    }

    /** The placeholder is translated too, so the switcher has to work behind it. */
    public function testSwitchingWorksWhileUnderConstruction(): void
    {
        $this->offerBoth();
        Setting::current()->update(['under_construction' => true]);

        $this->post(route('language.switch', 'nl'))->assertSessionHas('locale', 'nl');

        $this->get(route('home'))->assertSee('Deze website is nog in aanbouw');
    }

    private function offerBoth(): void
    {
        Setting::current()->update(['locales' => ['en', 'nl'], 'default_locale' => 'en']);
    }
}
