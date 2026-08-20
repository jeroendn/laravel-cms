<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsAreSentToLogin(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.settings.update'))->assertRedirect(route('login'));
    }

    public function testTheFormShowsTheCurrentSettings(): void
    {
        Setting::current()->update(['site_name' => 'The Dreaming', 'primary_color' => '#7c3aed']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('The Dreaming');
        $response->assertSee('#7c3aed');
    }

    public function testSettingsAreSaved(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.update'), $this->validPayload([
                'site_name' => 'The Dreaming',
                'primary_color' => '#7c3aed',
                'under_construction' => '1',
                'show_login_link' => '1',
                'locales' => ['en', 'nl'],
                'default_locale' => 'nl',
            ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $response->assertSessionHas('status');

        $settings = Setting::current();

        $this->assertSame('The Dreaming', $settings->site_name);
        $this->assertSame('#7c3aed', $settings->primary_color);
        $this->assertTrue($settings->under_construction);
        $this->assertTrue($settings->show_login_link);
        $this->assertSame(['en', 'nl'], $settings->locales);
        $this->assertSame('nl', $settings->default_locale);
    }

    /** Unchecked switches submit nothing at all, which has to mean "off". */
    public function testOmittedSwitchesTurnOff(): void
    {
        Setting::current()->update(['under_construction' => true, 'show_login_link' => true]);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.update'), $this->validPayload());

        $this->assertFalse(Setting::current()->under_construction);
        $this->assertFalse(Setting::current()->show_login_link);
    }

    /** An emptied name means "use APP_NAME again", not an empty header. */
    public function testAnEmptySiteNameFallsBackToTheConfiguredName(): void
    {
        Setting::current()->update(['site_name' => 'The Dreaming']);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.update'), $this->validPayload(['site_name' => '   ']));

        $this->assertNull(Setting::current()->site_name);
        $this->assertSame(config()->string('app.name'), Setting::current()->name());
    }

    public function testTheSiteNameReplacesTheConfiguredOneEverywhere(): void
    {
        Setting::current()->update(['site_name' => 'The Dreaming']);

        $response = $this->get(route('home'));

        $response->assertSee('<title>The Dreaming</title>', false);
        $response->assertDontSee(config()->string('app.name'));
    }

    public function testTheColorOverrideIsOnlyRenderedWhenItDiffersFromTheDefault(): void
    {
        $this->get(route('home'))->assertDontSee('--tblr-primary:', false);

        Setting::current()->update(['primary_color' => '#7c3aed']);

        $response = $this->get(route('home'));

        $response->assertSee('--tblr-primary:#7c3aed', false);
        $response->assertSee('[data-bs-theme=dark]', false);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('invalidPayloads')]
    public function testInvalidSettingsAreRejected(string $field, array $overrides): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.update'), [...$this->validPayload(), ...$overrides]);

        $response->assertSessionHasErrors($field);
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'not a colour' => ['primary_color', ['primary_color' => 'teal']],
            'shorthand colour' => ['primary_color', ['primary_color' => '#fff']],
            'no languages' => ['locales', ['locales' => []]],
            'unknown language' => ['locales.0', ['locales' => ['kl']]],
            'default not offered' => ['default_locale', ['locales' => ['en'], 'default_locale' => 'nl']],
        ];
    }

    /**
     * A new site stays hidden until its owner launches it. Tests\TestCase
     * turns the seeded flag off for the rest of the suite, so this rebuilds
     * the row the way the migration leaves it — from the column defaults.
     */
    public function testANewSiteIsUnderConstruction(): void
    {
        DB::table('settings')->delete();
        DB::table('settings')->insert(['locales' => json_encode(['en']), 'created_at' => now(), 'updated_at' => now()]);
        app()->forgetInstance(Setting::class);

        $this->assertTrue(Setting::current()->under_construction);
        $this->get(route('home'))->assertServiceUnavailable();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'site_name' => 'The Dreaming',
            'primary_color' => '#0f766e',
            'locales' => ['en'],
            'default_locale' => 'en',
            ...$overrides,
        ];
    }
}
