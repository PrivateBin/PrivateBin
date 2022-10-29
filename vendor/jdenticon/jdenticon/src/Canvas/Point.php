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

class Point
{
    /**
     * X coordinate.
     *
     * @var float
     */
    public $x;
    
    /**
     * Y coordinate.
     *
     * @var float
     */
    public $y;

    /**
     * Creates a new 2D point.
     *
     * @param float $x X coordinate.
     * @param float $y Y coordinate.
     */
    public function __construct($x, $y) 
    {
        $this->x = $x;
        $this->y = $y;
    }
}