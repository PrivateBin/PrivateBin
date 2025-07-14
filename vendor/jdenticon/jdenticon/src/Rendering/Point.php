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
    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * The X coordinate of this point.
     */
    public float $x;

    /**
     * The Y coordinate of this point.
     */
    public float $y;

    /**
     * Gets a string representation of the point.
     */
    public function __toString(): string
    {
        return $this->x . ", " . $this->y;
    }
}
