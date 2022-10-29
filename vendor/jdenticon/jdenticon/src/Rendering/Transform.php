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

/*
 * Translates and rotates a point before being rendered.
 */
class Transform
{
    private $x;
    private $y;
    private $size;
    private $rotation;

    /**
     * Creates a new Transform.
     *
     * @param float $x The x-coordinate of the upper left corner of the 
     *      transformed rectangle.
     * @param float $y The y-coordinate of the upper left corner of the 
     *      transformed rectangle.
     * @param float $size The size of the transformed rectangle.
     * @param integer $rotation Rotation specified as 
     *      0 = 0 rad, 1 = 0.5π rad, 2 = π rad, 3 = 1.5π rad.
     */
    public function __construct($x, $y, $size, $rotation)
    {
        $this->x = $x;
        $this->y = $y;
        $this->size = $size;
        $this->rotation = $rotation;
    }
    
    /**
     * Gets a noop transform.
     *
     * @return \Jdenticon\Rendering\Transform
     */
    public static function getEmpty() 
    {
        return new Transform(0, 0, 0, 0);
    }

    /**
     * Transforms the specified point based on the translation and rotation 
     * specification for this Transform.
     *
     * @param float $x x-coordinate
     * @param float $y y-coordinate
     * @param float $width The width of the transformed rectangle. If greater 
     *      than 0, this will ensure the returned point is of the upper left 
     *      corner of the transformed rectangle.
     * @param float $height The height of the transformed rectangle. If greater 
     *      than 0, this will ensure the returned point is of the upper left 
     *      corner of the transformed rectangle.
     * @return \Jdenticon\Rendering\Point
     */
    public function transformPoint($x, $y, $width = 0, $height = 0)
    {
        $right = $this->x + $this->size;
        $bottom = $this->y + $this->size;
        
        switch ($this->rotation) {
            case 1: return new Point($right - $y - $height, $this->y + $x);
            case 2: return new Point($right - $x - $width, $bottom - $y - $height);
            case 3: return new Point($this->x + $y, $bottom - $x - $width);
            default: return new Point($this->x + $x, $this->y + $y);
        }
    }
}
