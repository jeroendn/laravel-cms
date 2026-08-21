<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Turns the configured primary color into the custom properties Tabler
 * themes on. Everything else — hover, subtle backgrounds, focus rings — is
 * color-mixed from these by Tabler itself.
 */
class Theme
{
    /**
     * The <style> block for the head, or null while the color is the one
     * app.css already carries — which keeps its hand-picked light/dark pair
     * authoritative, and the fallback if this never renders.
     */
    public static function primaryStyle(): ?string
    {
        $primary = Setting::current()->primary_color;

        if ($primary === Setting::DEFAULT_PRIMARY_COLOR) {
            return null;
        }

        $light = Color::fromHex($primary);
        $dark = $light->forDarkTheme();

        return implode('', [
            ':root{' . self::properties($light) . '}',
            '[data-bs-theme=dark]{' . self::properties($dark) . '}',
        ]);
    }

    private static function properties(Color $color): string
    {
        return sprintf(
            '--tblr-primary:%s;--tblr-primary-rgb:%s;--tblr-primary-fg:%s;',
            $color->hex(),
            $color->rgb(),
            $color->needsDarkForeground() ? 'var(--tblr-gray-900)' : '#fff',
        );
    }
}
