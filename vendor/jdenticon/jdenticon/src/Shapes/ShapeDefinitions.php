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

namespace Jdenticon\Shapes;

use Jdenticon\Rendering\Point;
use Jdenticon\Rendering\TriangleDirection;

/**
 * Provides definitions for the default shapes used in identicons.
 */
class ShapeDefinitions
{
    private static $outerShapes;
    private static $centerShapes;
    
    /**
     * Gets an array of all possible outer shapes. Do not modify the returned
     * array.
     *
     * @return array(\Jdenticon\Rendering\Shape)
     */
    public static function getOuterShapes()
    {
        if (self::$outerShapes === null) {
            self::$outerShapes = self::createOuterShapes();
        }
        return self::$outerShapes;
    }
    
    /**
     * Gets an array of all possible center shapes. Do not modify the returned
     * array.
     *
     * @return array(\Jdenticon\Rendering\Shape)
     */
    public static function getCenterShapes()
    {
        if (self::$centerShapes === null) {
            self::$centerShapes = self::createCenterShapes();
        }
        return self::$centerShapes;
    }
    
    private static function createOuterShapes()
    {
        return array(
            function ($renderer, $cell, $index)
            {
                $renderer->addTriangle(0, 0, $cell, $cell, 0);
            },
            function ($renderer, $cell, $index)
            {
                $renderer->addTriangle(0, $cell / 2, $cell, $cell / 2, 0);
            },
            function ($renderer, $cell, $index)
            {
                $renderer->addRhombus(0, 0, $cell, $cell);
            },
            function ($renderer, $cell, $index)
            {
                $m = $cell / 6;
                $renderer->addCircle($m, $m, $cell - 2 * $m);
            }
        );
    }
    
    private static function createCenterShapes()
    {
        return array(
            function ($renderer, $cell, $index)
            {
                $k = $cell * 0.42;
                $renderer->addPolygon(array(
                    new Point(0, 0),
                    new Point($cell, 0),
                    new Point($cell, $cell - $k * 2),
                    new Point($cell - $k, $cell),
                    new Point(0, $cell)
                ));
            },
            function ($renderer, $cell, $index)
            {
                $w = (int)($cell * 0.5);
                $h = (int)($cell * 0.8);
                $renderer->addTriangle(
                    $cell - $w, 0, $w, $h, 
                    TriangleDirection::NORTH_EAST);
            },
            function ($renderer, $cell, $index)
            {
                $s = (int)($cell / 3);
                $renderer->addRectangle($s, $s, $cell - $s, $cell - $s);
            },
            function ($renderer, $cell, $index)
            {
                $tmp = $cell * 0.1;

                if ($tmp > 1) {
                    // large icon => truncate decimals
                    $inner = (int)$tmp;
                } elseif ($tmp > 0.5) {
                    // medium size icon => fixed width
                    $inner = 1;
                } else {
                    // small icon => anti-aliased border
                    $inner = $tmp;
                }

                // Use fixed outer border widths in small icons to ensure 
                // the border is drawn
                if ($cell < 6) {
                    $outer = 1;
                } elseif ($cell < 8) {
                    $outer = 2;
                } else {
                    $outer = (int)($cell / 4);
                }

                $renderer->addRectangle(
                    $outer, $outer, 
                    $cell - $inner - $outer, $cell - $inner - $outer);
            },
            function ($renderer, $cell, $index)
            {
                $m = (int)($cell * 0.15);
                $s = (int)($cell * 0.5);
                $renderer->addCircle($cell - $s - $m, $cell - $s - $m, $s);
            },
            function ($renderer, $cell, $index)
            {
                $inner = $cell * 0.1;
                $outer = $inner * 4;
                
                // Align edge to nearest pixel in large icons
                if ($outer > 3) {
                    $outer = (int)$outer;
                }

                $renderer->addRectangle(0, 0, $cell, $cell);
                $renderer->addPolygon(array(
                    new Point($outer, $outer),
                    new Point($cell - $inner, $outer),
                    new Point($outer + ($cell - $outer - $inner) / 2, 
                        $cell - $inner)
                ), true);
            },
            function ($renderer, $cell, $index)
            {
                $renderer->addPolygon(array(
                    new Point(0, 0),
                    new Point($cell, 0),
                    new Point($cell, $cell * 0.7),
                    new Point($cell * 0.4, $cell * 0.4),
                    new Point($cell * 0.7, $cell),
                    new Point(0, $cell)
                ));
            },
            function ($renderer, $cell, $index)
            {
                $renderer->addTriangle(
                    $cell / 2, $cell / 2, $cell / 2, $cell / 2, 
                    TriangleDirection::SOUTH_EAST);
            },
            function ($renderer, $cell, $index)
            {
                $renderer->addPolygon(array(
                    new Point(0, 0),
                    new Point($cell, 0),
                    new Point($cell, $cell / 2),
                    new Point($cell / 2, $cell),
                    new Point(0, $cell)
                ));
            },
            function ($renderer, $cell, $index)
            {
                $tmp = $cell * 0.14;
                
                if ($cell < 8) {
                     // small icon => anti-aliased border
                     $inner = $tmp;
                } else {
                     // large icon => truncate decimals
                     $inner = (int)$tmp;
                }
                
                // Use fixed outer border widths in small icons to ensure 
                // the border is drawn
                if ($cell < 4) {
                     $outer = 1;
                } elseif ($cell < 6) {
                     $outer = 2;
                } else {
                     $outer = (int)($cell * 0.35);
                }

                $renderer->addRectangle(0, 0, $cell, $cell);
                $renderer->addRectangle(
                    $outer, $outer, 
                    $cell - $outer - $inner, $cell - $outer - $inner, true);
            },
            function ($renderer, $cell, $index)
            {
                $inner = $cell * 0.12;
                $outer = $inner * 3;

                $renderer->addRectangle(0, 0, $cell, $cell);
                $renderer->addCircle($outer, $outer, $cell - $inner - $outer, 
                    true);
            },
            function ($renderer, $cell, $index)
            {
                $renderer->addTriangle(
                    $cell / 2, $cell / 2, $cell / 2, $cell / 2, 
                    TriangleDirection::SOUTH_EAST);
            },
            function ($renderer, $cell, $index)
            {
                $m = $cell * 0.25;

                $renderer->addRectangle(0, 0, $cell, $cell);
                $renderer->addRhombus($m, $m, $cell - $m, $cell - $m, true);
            },
            function ($renderer, $cell, $index)
            {
                $m = $cell * 0.4;
                $s = $cell * 1.2;

                if ($index != 0) {
                    $renderer->addCircle($m, $m, $s);
                }
            }
        );
    }
}
