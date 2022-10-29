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
 * A 2D coordinate.
 */
class Point
{
    /**
     * Creates a new Point.
     *
     * @param float $x X coordinate.
     * @param float $y Y coordinate.
     */
    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * The X coordinate of this point.
     *
     * @var float
     */
    public $x;

    /**
     * The Y coordinate of this point.
     *
     * @var float
     */
    public $y;

    /**
     * Gets a string representation of the point.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->x + ", " + $this->y;
    }
}
