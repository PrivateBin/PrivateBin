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
 * Specifies the bounds of a 2D rectangle.
 */
class Rectangle
{
    /**
     * The X coordinate of the left side of the rectangle.
     */
    public float $x;
    
    /**
     * The Y coordinate of the top side of the rectangle.
     */
    public float $y;
    
    /**
     * The width of the rectangle.
     */
    public float $width;
    
    /**
     * The height of the rectangle.
     */
    public float $height;

    /**
     * Creates a new Rectangle.
     *
     * @param float $x The X coordinate of the left edge of the rectangle.
     * @param float $y The Y coordinate of the top edge of the rectangle.
     * @param float $width The width of the rectangle.
     * @param float $height The height of the rectangle.
     */
    public function __construct(float $x, float $y, float $width, float $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }
}
