<?php
/**
 * This file is part of Jdenticon for PHP.
 * https://github.com/dmester/jdenticon-php/
 * 
 * Copyright (c) 2018 Daniel Mester Pirttijärvi
 * 
 * For full license information, please see the LICENSE file that was 
 * distributed with this source code.
 */

namespace Jdenticon\Rendering;

use Jdenticon\Color;

/**
 * Specifies the colors to be used in an identicon.
 */
class ColorTheme
{
    private $darkGray;
    private $midColor;
    private $lightGray;
    private $lightColor;
    private $darkColor;
    
    /**
     * Creates a new ColorTheme.
     *
     * @param float $hue The hue of the colored shapes in the range [0, 1].
     * @param \Jdenticon\IdenticonStyle $style The style that specifies the 
     *      lightness and saturation of the icon.
     */
    public function __construct($hue, \Jdenticon\IdenticonStyle $style)
    {
        $grayscaleLightness = $style->getGrayscaleLightness();
        $colorLightness = $style->getColorLightness();
        $hues = $style->getHues();

        if ($hues !== null) {
            // $hue is in the range [0, 1]
            // Multiply with 0.999 to change the range to [0, 1)
            $hueIndex = (int)($hue * 0.999 * count($hues));
            $hue = (float)$hues[$hueIndex] / 360;
        }
        
        $this->darkGray = Color::fromHslCompensated(
            $hue, $style->getGrayscaleSaturation(), $grayscaleLightness[0]);
        $this->midColor = Color::fromHslCompensated(
            $hue, $style->getColorSaturation(), ($colorLightness[0] + $colorLightness[1]) / 2);
        $this->lightGray = Color::fromHslCompensated(
            $hue, $style->getGrayscaleSaturation(), $grayscaleLightness[1]);
        $this->lightColor = Color::fromHslCompensated(
            $hue, $style->getColorSaturation(), $colorLightness[1]);
        $this->darkColor = Color::fromHslCompensated(
            $hue, $style->getColorSaturation(), $colorLightness[0]);
    }

    /**
     * Gets a color from this color theme by index.
     *
     * @param int $index Color index in the range [0, getCount()).
     * @return Jdenticon\Color
     */
    public function getByIndex($index)
    {
        if ($index === 0) return $this->darkGray;
        if ($index === 1) return $this->midColor;
        if ($index === 2) return $this->lightGray;
        if ($index === 3) return $this->lightColor;
        if ($index === 4) return $this->darkColor;
        return null;
    }
    
    /**
     * Gets the number of available colors in this theme.
     *
     * @return int
     */
    public function getCount() 
    {
        return 5;
    }
}
