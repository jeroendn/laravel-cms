<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The locale is settled per request by the SetLocale middleware, so these
     * ask for it the way a visitor does — through the session — rather than
     * with app()->setLocale(), which the middleware would overwrite. The
     * languages have to be offered before they can be picked.
     */
    public function testLoginPageIsShownInDutch(): void
    {
        $this->offering(['en', 'nl']);

        $response = $this->withSession(['locale' => 'nl'])->get(route('login'));

        $response->assertSee('Inloggen');
        $response->assertSee('E-mailadres');
        $response->assertSee('Wachtwoord');
    }

    public function testLoginPageFallsBackToEnglish(): void
    {
        $this->offering(['en', 'nl']);

        $response = $this->withSession(['locale' => 'en'])->get(route('login'));

        $response->assertSee('Login');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
    }

    public function testTheLayoutIsShownInDutch(): void
    {
        $this->offering(['en', 'nl']);

        $response = $this->withSession(['locale' => 'nl'])->get(route('home'));

        // The burger button's aria-label — a string every public page renders.
        $response->assertSee('Schakel navigatie');
    }

    /** Without a preference of their own, a visitor gets the site's default. */
    public function testAVisitorWithoutAChoiceGetsTheDefaultLanguage(): void
    {
        $this->offering(['en', 'nl'], 'nl');

        $this->get(route('login'))->assertSee('Inloggen');
    }

    /** A language the site no longer offers cannot be forced through. */
    public function testAnUnofferedLanguageIsIgnored(): void
    {
        $this->offering(['en']);

        $this->withSession(['locale' => 'nl'])->get(route('login'))->assertSee('Login');
    }

    /**
     * @param non-empty-list<string> $locales
     */
    private function offering(array $locales, ?string $default = null): void
    {
        Setting::current()->update([
            'locales' => $locales,
            'default_locale' => $default ?? $locales[0],
        ]);
    }
}
