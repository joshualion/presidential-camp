<?php

namespace Tests\Unit;

use App\Support\ThemeColor;
use PHPUnit\Framework\TestCase;

class ThemeColorTest extends TestCase
{
    public function test_theme_colours_accept_only_hexadecimal_css_values(): void
    {
        $this->assertSame('#aabbcc', ThemeColor::normalize(' #AbC '));
        $this->assertSame('#008751', ThemeColor::normalize('red; background:url(https://example.test)'));
        $this->assertSame('#ffffff', ThemeColor::foreground('#000'));
        $this->assertSame('#000000', ThemeColor::foreground('#fff'));
    }

    public function test_selected_foreground_meets_aa_across_the_rgb_space(): void
    {
        for ($r = 0; $r <= 255; $r += 17) {
            for ($g = 0; $g <= 255; $g += 17) {
                for ($b = 0; $b <= 255; $b += 17) {
                    $hex = sprintf('#%02x%02x%02x', $r, $g, $b);
                    $channels = array_map(static function ($channel) {
                        $value = $channel / 255;
                        return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
                    }, [$r, $g, $b]);
                    $luminance = .2126 * $channels[0] + .7152 * $channels[1] + .0722 * $channels[2];
                    $ratio = ThemeColor::foreground($hex) === '#ffffff' ? 1.05 / ($luminance + .05) : ($luminance + .05) / .05;
                    $this->assertGreaterThanOrEqual(4.5, $ratio, $hex);
                }
            }
        }
    }
}
