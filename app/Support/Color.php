<?php

namespace App\Support;

use Spatie\Color\CIELab;
use Spatie\Color\Contrast;
use Spatie\Color\Hex;

/**
 * The color work the configurable primary needs on top of spatie/color:
 * Tabler wants an "r, g, b" triple next to every hex, the dark theme needs a
 * readable variant of the same hue, and a button needs a legible foreground.
 */
readonly class Color
{
    /**
     * CIELab lightness, not HSL's: HSL's is not perceptual, so at HSL 40% a
     * teal clears 7:1 on Tabler's dark body while a crimson only reaches 2.8:1
     * and violet is passed through untouched. Measured 2026-08-21: at L* 60
     * every hue tried lands within 5.8:1 - 6.0:1.
     */
    private const float DARK_THEME_LIGHTNESS = 60.0;

    private function __construct(private Hex $hex) {}

    /** Accepts a bare hex too; spatie/color insists on the leading #. */
    public static function fromHex(string $hex): self
    {
        return new self(Hex::fromString('#' . ltrim(trim($hex), '#')));
    }

    public function hex(): string
    {
        return (string) $this->hex;
    }

    /** The triple Tabler mixes its hover/subtle/focus variants from. */
    public function rgb(): string
    {
        $rgb = $this->hex->toRgb();

        return sprintf('%d, %d, %d', $rgb->red(), $rgb->green(), $rgb->blue());
    }

    public function forDarkTheme(): self
    {
        $lab = $this->hex->toCIELab();

        if ($lab->l() >= self::DARK_THEME_LIGHTNESS) {
            return $this;
        }

        return new self(new CIELab(self::DARK_THEME_LIGHTNESS, $lab->a(), $lab->b())->toHex());
    }

    /** Tabler's --tblr-primary-fg is white, which vanishes on a light primary. */
    public function needsDarkForeground(): bool
    {
        return Contrast::ratio($this->hex, Hex::fromString('#000000'))
            > Contrast::ratio($this->hex, Hex::fromString('#ffffff'));
    }
}
