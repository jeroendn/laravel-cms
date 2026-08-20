<?php

namespace App\Support;

/**
 * The color math the configurable primary needs. Tabler wants an "r, g, b"
 * triple next to every hex, the dark theme needs a lighter variant of the same
 * hue, and a button needs a foreground that survives on top of the color.
 */
readonly class Color
{
    /**
     * The lightness the dark theme's primary is raised to. app.css shipped
     * the pair #0f766e / #14b8a6 by hand — hsl(175, 77%, 26%) and
     * hsl(173, 80%, 40%) — i.e. the same color, light enough to read on
     * Tabler's dark body.
     */
    private const float DARK_THEME_LIGHTNESS = 0.4;

    /**
     * Where white and black text reach equal contrast on a background:
     * (1.05 / (L + 0.05)) == ((L + 0.05) / 0.05) solves to L ≈ 0.1791.
     */
    private const float FOREGROUND_CROSSOVER = 0.1791;

    private function __construct(
        private int $red,
        private int $green,
        private int $blue,
    ) {}

    /**
     * @param string $hex six digits, with or without the leading #
     */
    public static function fromHex(string $hex): self
    {
        $hex = ltrim(trim($hex), '#');

        return new self(
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        );
    }

    public function hex(): string
    {
        return sprintf('#%02x%02x%02x', $this->red, $this->green, $this->blue);
    }

    /**
     * The triple Tabler keeps next to every color custom property, so it can
     * mix its own hover/subtle/focus variants from it.
     */
    public function rgb(): string
    {
        return sprintf('%d, %d, %d', $this->red, $this->green, $this->blue);
    }

    /**
     * The same color at the lightness the dark theme needs. Already-light
     * colors are left alone.
     */
    public function forDarkTheme(): self
    {
        [$hue, $saturation, $lightness] = $this->toHsl();

        return self::fromHsl($hue, $saturation, max($lightness, self::DARK_THEME_LIGHTNESS));
    }

    /**
     * Whether text on this color has to be dark. Tabler's --tblr-primary-fg
     * is white by default, which vanishes on a light primary.
     */
    public function needsDarkForeground(): bool
    {
        return $this->relativeLuminance() > self::FOREGROUND_CROSSOVER;
    }

    /**
     * @return array{float, float, float} hue in degrees, saturation and
     *                                    lightness as fractions
     */
    private function toHsl(): array
    {
        $red = $this->red / 255;
        $green = $this->green / 255;
        $blue = $this->blue / 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $chroma = $max - $min;
        $lightness = ($max + $min) / 2;

        if ($chroma === 0.0) {
            return [0.0, 0.0, $lightness];
        }

        $saturation = $chroma / (1 - abs(2 * $lightness - 1));

        $hue = match ($max) {
            $red => fmod(($green - $blue) / $chroma, 6),
            $green => ($blue - $red) / $chroma + 2,
            default => ($red - $green) / $chroma + 4,
        };

        return [fmod($hue * 60 + 360, 360), $saturation, $lightness];
    }

    private static function fromHsl(float $hue, float $saturation, float $lightness): self
    {
        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $second = $chroma * (1 - abs(fmod($hue / 60, 2) - 1));
        $match = $lightness - $chroma / 2;

        [$red, $green, $blue] = match ((int) ($hue / 60)) {
            0 => [$chroma, $second, 0.0],
            1 => [$second, $chroma, 0.0],
            2 => [0.0, $chroma, $second],
            3 => [0.0, $second, $chroma],
            4 => [$second, 0.0, $chroma],
            default => [$chroma, 0.0, $second],
        };

        return new self(
            (int) round(($red + $match) * 255),
            (int) round(($green + $match) * 255),
            (int) round(($blue + $match) * 255),
        );
    }

    /**
     * WCAG 2.1 relative luminance.
     */
    private function relativeLuminance(): float
    {
        return 0.2126 * self::linearize($this->red)
            + 0.7152 * self::linearize($this->green)
            + 0.0722 * self::linearize($this->blue);
    }

    private static function linearize(int $value): float
    {
        $channel = $value / 255;

        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
