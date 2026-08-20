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
     * a settings row naming none at all falls back to the site's own locale,
     * which leaves the switcher hidden.
     *
     * @return non-empty-list<string>
     */
    public static function enabled(): array
    {
        $enabled = array_values(array_intersect(array_keys(self::AVAILABLE), Setting::current()->locales));

        if ($enabled === []) {
            return [self::configured()];
        }

        return $enabled;
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
     * The settings' default locale, narrowed to one the app ships.
     */
    private static function configured(): string
    {
        $default = Setting::current()->default_locale;

        return isset(self::AVAILABLE[$default]) ? $default : (string) array_key_first(self::AVAILABLE);
    }
}
