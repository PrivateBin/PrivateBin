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
 * Base class for rendering shapes in an identicon. Implement this class to e.g. 
 * support a new file format that is not natively supported by Jdenticon. To 
 * invoke the new Renderer, pass the renderer as an argument to the 
 * {@see \Jdenticon\Identicon::Draw} method.
 */
abstract class AbstractRenderer implements RendererInterface
{
    private $transform;
    protected $backgroundColor;
    
    public function __construct()
    {
        $this->transform = Transform::getEmpty();
    }
    
    /**
     * Sets the current transform that will be applied on all coordinates before 
     * being rendered to the target image.
     *
     * @param  \Jdenticon\Rendering\Transform $transform  The transform to set. 
     *      If NULL is specified any existing transform is removed.
     */
    public function setTransform(\Jdenticon\Rendering\Transform $transform) 
    {
        $this->transform = $transform === null ? 
            Transform::getEmpty() : $transform;
    }
    
    /**
     * Gets the current transform that will be applied on all coordinates before 
     * being rendered to the target image.
     *
     * @return \Jdenticon\Rendering\Transform
     */
    public function getTransform() 
    {
        return $this->transform;
    }
    
    /**
     * Adds a polygon without translating its coordinates.
     *
     * @param  array $points An array of the points that the polygon consists of.
     */
    abstract protected function addPolygonNoTransform($points);

    /**
     * Adds a circle without translating its coordinates.
     *
     * @param float $x The x-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $size The size of the bounding rectangle.
     * @param bool $counterClockwise If true the circle will be drawn 
     *      counter clockwise.
     */
    abstract protected function addCircleNoTransform($x, $y, $size, $counterClockwise);

    /**
     * Sets the background color of the image.
     *
     * @param \Jdenticon\Color $color  The image background color.
     */
    public function setBackgroundColor(\Jdenticon\Color $color)
    {
        $this->backgroundColor = $color;
    }
    
    /**
     * Gets the background color of the image.
     *
     * @return \Jdenticon\Color
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }
    
    private function addPolygonCore(array $points, $invert)
    {
        $transformedPoints = array();
        foreach ($points as $point) {
            $transformedPoints[] = 
                $this->transform->transformPoint($point->x, $point->y);
        }
        
        if ($invert) {
            $transformedPoints = array_reverse($transformedPoints);
        }
        
        //var_dump($transformedPoints);
        
        $this->addPolygonNoTransform($transformedPoints);
    }

    /**
     * Adds a rectangle to the image.
     *
     * @param float $x The x-coordinate of the rectangle upper-left corner.
     * @param float $y The y-coordinate of the rectangle upper-left corner.
     * @param float $width The width of the rectangle.
     * @param float $height The height of the rectangle.
     * @param bool $invert If true the area of the rectangle will be removed 
     *      from the filled area.
     */
    public function addRectangle($x, $y, $width, $height, $invert = false)
    {
        $this->addPolygonCore(array(
            new Point($x, $y),
            new Point($x + $width, $y),
            new Point($x + $width, $y + $height),
            new Point($x, $y + $height),
        ), $invert);
    }

    /**
     * Adds a circle to the image.
     *
     * @param float $x The x-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $size The size of the bounding rectangle.
     * @param bool $invert If true the area of the circle will be removed 
     *      from the filled area.
     */
    public function addCircle($x, $y, $size, $invert = false)
    {
        $northWest = $this->transform->transformPoint($x, $y, $size, $size);
        $this->addCircleNoTransform($northWest->x, $northWest->y, $size, $invert);
    }

    /**
     * Adds a polygon to the image.
     *
     * @param array $points Array of points that the polygon consists of.
     * @param bool $invert If true the area of the polygon will be removed 
     *      from the filled area.
     */
    public function addPolygon($points, $invert = false)
    {
        $this->addPolygonCore($points, $invert);
    }

    /**
     * Adds a triangle to the image.
     *
     * @param float $x The x-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $width The width of the bounding rectangle.
     * @param float $height The height of the bounding rectangle.
     * @param float $direction The direction of the 90 degree corner of the 
     *      triangle.
     * @param bool $invert If true the area of the triangle will be removed 
     *      from the filled area.
     */
    public function addTriangle($x, $y, $width, $height, $direction, $invert = false)
    {
        $points = array(
            new Point($x + $width, $y),
            new Point($x + $width, $y + $height),
            new Point($x, $y + $height),
            new Point($x, $y)
        );

        array_splice($points, $direction, 1);
        
        $this->addPolygonCore($points, $invert);
    }

    /**
     * Adds a rhombus to the image.
     *
     * @param float $x The x-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $width The width of the bounding rectangle.
     * @param float $height The height of the bounding rectangle.
     * @param bool $invert If true the area of the rhombus will be removed 
     *      from the filled area.
     */
    public function addRhombus($x, $y, $width, $height, $invert = false)
    {
        $this->addPolygonCore(array(
            new Point($x + $width / 2, $y),
            new Point($x + $width, $y + $height / 2),
            new Point($x + $width / 2, $y + $height),
            new Point($x, $y + $height / 2),
        ), $invert);
    }
}
