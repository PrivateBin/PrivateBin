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

namespace Jdenticon\Canvas;

class ColorUtils
{
    /**
     * Transparent color.
     * @var integer
     */
    const TRANSPARENT = 0;

    /**
     * Specifies a transparent color that will not blend with layers below the 
     * current layer.
     *
     * @var float
     */
    const FORCE_TRANSPARENT = INF;

    /**
     * Creates a color on the format 0xRRGGBBAA from the specified
     * color components.
     *
     * @return integer
     */
    public static function from($a, $r, $g, $b) 
    {
        return ($r << 24) | ($g << 16) | ($b << 8) | $a;
    }

    /**
     * Gets the alpha component of a color.
     *
     * @param integer $color  32-bit color value on the format 0xRRGGBBAA.
     * @return integer Alpha in the range [0, 255].
     */
    public static function alpha($color) 
    {
        return $color & 0xff;
    }

    /**
     * Gets the red component of a color.
     *
     * @param integer $color  32-bit color value on the format 0xRRGGBBAA.
     * @return integer Red component in the range [0, 255].
     */
    public static function red($color) 
    {
        return ($color >> 24) & 0xff;
    }

    /**
     * Gets the green component of a color.
     *
     * @param integer $color  32-bit color value on the format 0xRRGGBBAA.
     * @return integer Green component in the range [0, 255].
     */
    public static function green($color) 
    {
        return ($color >> 16) & 0xff;
    }

    /**
     * Gets the blue component of a color.
     *
     * @param integer $color  32-bit color value on the format 0xRRGGBBAA.
     * @return integer Blue component in the range [0, 255].
     */
    public static function blue($color) 
    {
        return ($color >> 8) & 0xff;
    }
    
    /**
     * Formats a color as a string.
     *
     * @param integer $color Color to format.
     * @return string
     */
    public static function format($color) 
    {
        return bin2hex(pack('N', $color));
    }
    
    /**
     * Computes a mix of the two specified colors, with the proportion given 
     * by the specified weight.
     *
     * @param integer $color1  First color to mix.
     * @param integer $color2  Second color to mix.
     * @param float $weight  Weight in the range [0,1]. 
     *      0 gives $color1, 1 gives $color2.
     * @return integer Mixed color.
     */
    public static function mix($color1, $color2, $weight) 
    {
        if ($weight < 0) {
            $weight = 0;
        } elseif ($weight > 1) {
            $weight = 1;
        }
        
        $a = ($color1 & 0xff) * (1 - $weight) + ($color2 & 0xff) * $weight;
        if ($a <= 0.1) {
            return 0;
        }
        
        $r = (
            ($color1 >> 24) * ($color1 & 0xff) * (1 - $weight) +
            ($color2 >> 24) * ($color2 & 0xff) * $weight
            ) / $a;
            
        $g = (
            (($color1 >> 16) & 0xff) * ($color1 & 0xff) * (1 - $weight) +
            (($color2 >> 16) & 0xff) * ($color2 & 0xff) * $weight
            ) / $a;
            
        $b = (
            (($color1 >> 8) & 0xff) * ($color1 & 0xff) * (1 - $weight) +
            (($color2 >> 8) & 0xff) * ($color2 & 0xff) * $weight
            ) / $a;
        
        if ($a > 255) $a = 255;    
        if ($r > 255) $r = 255;
        if ($g > 255) $g = 255;
        if ($b > 255) $b = 255;
        
        return ((int)$r << 24) | ((int)$g << 16) | ((int)$b << 8) | (int)$a;
    }

    /**
     * Parses a value to a 32-bit color on the format 0xRRGGBBAA.
     *
     * @param integer|string $color  The value to parse.
     * @return integer
     */
    public static function parse($color) 
    {
        if (gettype($color) == "integer") {
            return $color & 0xffffffff;
        }

        $color = "$color";

        if (preg_match('/^#?[0-9a-fA-F]+$/', $color)) {
            $hexColor = $color; 
            if ($hexColor[0] == '#') {
                $hexColor = substr($hexColor, 1);
            }

            switch (strlen($hexColor)) {
                case 3:
                    $numeric = intval($hexColor, 16);
                    return (
                        (($numeric & 0xf00) << 20) |
                        (($numeric & 0xf00) << 16) |
                        (($numeric & 0x0f0) << 16) |
                        (($numeric & 0x0f0) << 12) |
                        (($numeric & 0x00f) << 12) |
                        (($numeric & 0x00f) << 8) | 
                        0xff);
                case 6:
                    return (intval($hexColor, 16) << 8) | 0xff;
                case 8:
                    // Workaround to cope with PHP limitation of intval
                    $numeric = 
                        (intval(substr($hexColor, 0, 4), 16) << 16) |
                        (intval(substr($hexColor, 4, 4), 16));
                    return $numeric;
            }
        }

        throw new \InvalidArgumentException("Invalid color '$color'.");
    }

    /**
     * Blends this color with another color using the over blending operation.
     *
     * @param integer $fore  The foreground color.
     * @param integer $back  The background color.
     * @return integer
     */
    public static function over($fore, $back) 
    {
        $foreA = ($fore & 0xff);
        $backA = ($back & 0xff);

        if ($foreA < 1) {
            return $back;
        } elseif ($foreA > 254 || $backA < 1) {
            return $fore;
        }

        // Source: 
        // https://en.wikipedia.org/wiki/Alpha_compositing#Description
        $forePA = $foreA * 255;
        $backPA = $backA * (255 - $foreA);
        $pa = ($forePA + $backPA);

        $b = (int) (
            ($forePA * (($fore >> 8) & 0xff) + $backPA * (($back >> 8) & 0xff)) /
            $pa);

        $g = (int) (
            ($forePA * (($fore >> 16) & 0xff) + $backPA * (($back >> 16) & 0xff)) /
            $pa);

        $r = (int) (
            ($forePA * (($fore >> 24) & 0xff) + $backPA * (($back >> 24) & 0xff)) /
            $pa);

        $a = (int) ($pa / 255);

        return ($r << 24) | ($g << 16) | ($b << 8) | $a;
    }
};

