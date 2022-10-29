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

/**
 * Represents a SVG path element.
 */
class SvgPath
{
    private $dataString;
    
    public function __construct() 
    {
        $this->dataString = '';
    }

    /**
     * Adds a circle to the SVG.
     *
     * @param float $x X coordinate of the left side of the containing rectangle.
     * @param float $y Y coordinate of the top side of the containing rectangle.
     * @param float $size The diameter of the circle.
     * @param bool $counterClockwise If true the circle will be drawn counter 
     *      clockwise. This affects the rendering since the evenodd filling rule 
     *      is used by Jdenticon.
     */
    public function addCircle($x, $y, $size, $counterClockwise)
    {
        $sweepFlag = $counterClockwise ? '0' : '1';
        $radiusAsString = number_format($size / 2, 2, '.', '');

        $this->dataString .=
            'M'. number_format($x, 2, '.', '') .' '. 
                number_format($y + $size / 2, 2, '.', '').
            'a'. $radiusAsString .','. $radiusAsString .' 0 1,'. 
                $sweepFlag .' '. number_format($size, 2, '.', '') .',0'.
            'a'. $radiusAsString .','. $radiusAsString .' 0 1,'. 
                $sweepFlag .' '. number_format(-$size, 2, '.', '') .',0';
    }

    /**
     * Adds a polygon to the SVG.
     *
     * @param array(\Jdenticon\Rendering\Point) $points The corners of the 
     *      polygon.
     */
    public function addPolygon($points)
    {
        $pointCount = count($points);

        $this->dataString .= 'M'. 
            number_format($points[0]->x, 2, '.', '') .' '. 
            number_format($points[0]->y, 2, '.', '');

        for ($i = 1; $i < $pointCount; $i++) {
            $this->dataString .= 'L'. 
                number_format($points[$i]->x, 2, '.', '') .' '. 
                number_format($points[$i]->y, 2, '.', '');
        }

        $this->dataString .= 'Z';
    }

    /**
     * Gets the path as a SVG path string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->dataString;
    }
}

