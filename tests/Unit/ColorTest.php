<?php

namespace Tests\Unit;

use App\Support\Color;
use Tests\TestCase;

class ColorTest extends TestCase
{
    public function testHexSurvivesTheRoundTrip(): void
    {
        $this->assertSame('#0f766e', Color::fromHex('#0f766e')->hex());
        $this->assertSame('#0f766e', Color::fromHex('0F766E')->hex());
    }

    public function testRgbIsTheTripleTablerWants(): void
    {
        $this->assertSame('15, 118, 110', Color::fromHex('#0f766e')->rgb());
    }

    /**
     * The rule was read off app.css's hand-picked pair, so the derived value
     * has to land on #14b8a6 give or take a rounding step. A real drift means
     * the rule stopped describing that pair.
     */
    public function testTheDarkVariantMatchesTheHandPickedPair(): void
    {
        $dark = Color::fromHex('#0f766e')->forDarkTheme();

        foreach ([0 => 0x14, 1 => 0xb8, 2 => 0xa6] as $index => $expected) {
            $actual = (int) hexdec(substr($dark->hex(), 1 + $index * 2, 2));

            $this->assertLessThanOrEqual(4, abs($actual - $expected), $dark->hex());
        }
    }

    public function testAlreadyLightColorsAreLeftAlone(): void
    {
        $this->assertSame('#7dd3fc', Color::fromHex('#7dd3fc')->forDarkTheme()->hex());
    }

    /** The achromatic path: no hue to preserve, and already at 40% lightness. */
    public function testGreyIsUnchanged(): void
    {
        $this->assertSame('#666666', Color::fromHex('#666666')->forDarkTheme()->hex());
    }

    public function testTheForegroundFollowsTheBackground(): void
    {
        // The current pair: white on the light primary, dark on the lighter
        // one — which is exactly what app.css sets by hand.
        $this->assertFalse(Color::fromHex('#0f766e')->needsDarkForeground());
        $this->assertTrue(Color::fromHex('#14b8a6')->needsDarkForeground());

        $this->assertTrue(Color::fromHex('#ffffff')->needsDarkForeground());
        $this->assertFalse(Color::fromHex('#000000')->needsDarkForeground());
    }
}
