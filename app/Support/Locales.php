<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The languages the site can be shown in. A fixed list rather than a scan of
 * lang/: the native labels have to be written down somewhere anyway (ext-intl
 * is not installed), and a translation directory existing does not mean the
 * site should offer it. A new language costs one entry here plus
 * `artisan lang:add <locale>`.
 */
class Locales
{
    /** @var array<string, string> */
    private const array AVAILABLE = [
        'en' => 'English',
        'nl' => 'Nederlands',
    ];

    /**
     * @return array<string, string> locale code => native label
     */
    public static function available(): array
    {
        return self::AVAILABLE;
    }

    /**
     * The locales the site currently offers, in the order above. Never empty:
     * a settings row naming none the app ships falls back to the first
     * language above, which leaves the switcher hidden.
     *
     * @return non-empty-list<string>
     */
    public static function enabled(): array
    {
        $enabled = array_values(array_intersect(array_keys(self::AVAILABLE), Setting::current()->locales));

        return $enabled === [] ? [(string) array_key_first(self::AVAILABLE)] : $enabled;
    }

    /**
     * The locale a visitor without a preference gets — the configured
     * default, unless the site does not actually offer it (anymore).
     */
    public static function defaultLocale(): string
    {
        $enabled = self::enabled();
        $default = Setting::current()->default_locale;

        return in_array($default, $enabled, true) ? $default : $enabled[0];
    }

    public static function isEnabled(string $locale): bool
    {
        return in_array($locale, self::enabled(), true);
    }

    public static function label(string $locale): string
    {
        return self::AVAILABLE[$locale] ?? strtoupper($locale);
    }

    /**
     * The language a fresh install starts out in. Seeding a fixed 'en' would
     * flip every existing site to English on the deploy that adds the
     * settings row, since SetLocale reads that row instead of APP_LOCALE
     * from there on.
     */
    public static function configuredDefault(): string
    {
        $locale = config()->string('app.locale');

        return isset(self::AVAILABLE[$locale]) ? $locale : (string) array_key_first(self::AVAILABLE);
    }
}
