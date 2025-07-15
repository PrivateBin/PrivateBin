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

namespace Jdenticon\Canvas;

class Point
{
    /**
     * X coordinate.
     */
    public float $x;
    
    /**
     * Y coordinate.
     */
    public float $y;

    /**
     * Creates a new 2D point.
     *
     * @param float $x X coordinate.
     * @param float $y Y coordinate.
     */
    public function __construct(float $x, float $y) 
    {
        $this->x = $x;
        $this->y = $y;
    }
}