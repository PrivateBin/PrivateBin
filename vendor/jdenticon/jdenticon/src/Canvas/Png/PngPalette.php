<?php
/**
 * This file is part of Jdenticon for PHP.
 * https://github.com/dmester/jdenticon-php/
 * 
 * Copyright (c) 2025 Daniel Mester Pirttijärvi
 * 
 * For full license information, please see the LICENSE file that was 
 * distributed with this source code.
 */

namespace Jdenticon\Canvas\Png;

/**
 * Contains the colors of a PNG color palette.
 */
class PngPalette
{
    /**
     * Creates a PNG color palette for the specified bitmap data.
     *
     * @param array<int> $colorRanges Array of interleaved values on the 
     *      format [count0, color0, count1, color1, ...]
     */
    function __construct(array &$colorRanges) 
    {
        $lookup = [];
        $colors = [];
        $hasAlphaChannel = false;
        $colorsCount = 0;

        $count = -1;

        foreach ($colorRanges as $value) {
            if ($count === -1) {
                $count = $value;
            } else {
                // Ignore empty ranges and already indexed colors
                if ($count > 0 && !isset($lookup[$value])) {
                    if (!$hasAlphaChannel && ($value & 0xff) < 255) {
                        $hasAlphaChannel = true;
                    }

                    $lookup[$value] = $colorsCount++;
                    $colors[] = $value;
                    
                    if ($colorsCount > 256) {
                        break;
                    }
                }
                
                $count = -1;
            }
        }

        $this->hasAlphaChannel = $hasAlphaChannel;
        $this->colors = & $colors;
        $this->lookup = & $lookup;
        $this->isValid = $colorsCount <= 256;
    }

    /**
     * Specifies if the palette is valid to be used for encoding a PNG image.
     */
    public bool $isValid;

    /**
     * Specifies if the palette has any partial or fully transparent
     * colors.
     */
    public bool $hasAlphaChannel;

    /**
     * Array of colors in the palette.
     */
    public array $colors;

    /**
     * Lookup table from 32-bit color value to color index.
     */
    public array $lookup;
}
