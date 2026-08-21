<?php

namespace Tests\Unit;

use App\Support\Color;
use Spatie\Color\Hex;
use Tests\TestCase;

class ColorTest extends TestCase
{
    public function testHexSurvivesTheRoundTrip(): void
    {
        $this->assertSame('#750f2e', Color::fromHex('#750f2e')->hex());
        $this->assertSame('#750f2e', Color::fromHex('750F2E')->hex());
    }

    public function testRgbIsTheTripleTablerWants(): void
    {
        $this->assertSame('117, 15, 46', Color::fromHex('#750f2e')->rgb());
    }

    /** app.css ships this pair by hand; a change to either side needs both. */
    public function testTheDarkVariantMatchesThePairInAppCss(): void
    {
        $this->assertSame('#da6f81', Color::fromHex('#750f2e')->forDarkTheme()->hex());
        $this->assertSame('218, 111, 129', Color::fromHex('#750f2e')->forDarkTheme()->rgb());
    }

    public function testAlreadyLightColorsAreLeftAlone(): void
    {
        $this->assertSame('#7dd3fc', Color::fromHex('#7dd3fc')->forDarkTheme()->hex());
    }

    /** Why the lightness is CIELab's: HSL's is not comparable across hues. */
    public function testEveryHueIsRaisedToTheSamePerceivedLightness(): void
    {
        foreach (['#750f2e', '#0f766e', '#7c3aed', '#b91c1c', '#1d4ed8', '#166534'] as $hex) {
            $lightness = $this->perceivedLightness(Color::fromHex($hex)->forDarkTheme()->hex());

            $this->assertEqualsWithDelta(60.0, $lightness, 1.0, $hex);
        }
    }

    public function testTheForegroundFollowsTheBackground(): void
    {
        $this->assertFalse(Color::fromHex('#750f2e')->needsDarkForeground());
        $this->assertTrue(Color::fromHex('#da6f81')->needsDarkForeground());

        $this->assertTrue(Color::fromHex('#ffffff')->needsDarkForeground());
        $this->assertFalse(Color::fromHex('#000000')->needsDarkForeground());
    }

    private function perceivedLightness(string $hex): float
    {
        return Hex::fromString($hex)->toCIELab()->l();
    }
}
