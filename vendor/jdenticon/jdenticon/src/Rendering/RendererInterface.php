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

use Jdenticon\Color;

/**
 * Interface for an identicon renderer.
 */
interface RendererInterface
{
    /**
     * Sets the current transform that will be applied on all coordinates before
     * being rendered to the target image.
     *
     * @param \Jdenticon\Rendering\Transform $transform The transform to set. 
     *      If NULL is specified any existing transform is removed.
     */
    public function setTransform(\Jdenticon\Rendering\Transform $transform);
    
    /**
     * Gets the current transform that will be applied on all coordinates before 
     * being rendered to the target image.
     *
     * @return \Jdenticon\Rendering\Transform
     */
    public function getTransform();
    
    /**
     * Sets the background color of the image.
     *
     * @param \Jdenticon\Color $color The image background color.
     */
    public function setBackgroundColor(Color $color);
    
    /**
     * Gets the background color of the image.
     *
     * @return \Jdenticon\Color
     */
    public function getBackgroundColor();
    
    /**
     * Gets the MIME type of the renderer output.
     *
     * @return string
     */
    public function getMimeType();
    
    /**
     * Begins a new shape. The shape should be ended with a call to endShape.
     *
     * @param \Jdenticon\Color $color The color of the shape.
     */
    public function beginShape(Color $color);
    
    /**
     * Ends the currently drawn shape.
     */
    public function endShape();

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
    public function addRectangle($x, $y, $width, $height, $invert = false);

    /**
     * Adds a circle to the image.
     *
     * @param float $x The x-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $size The size of the bounding rectangle.
     * @param bool $invert If true the area of the circle will be removed from 
     *      the filled area.
     */
    public function addCircle($x, $y, $size, $invert = false);

    /**
     * Adds a polygon to the image.
     *
     * @param array $points Array of points that the polygon consists of.
     * @param bool $invert If true the area of the polygon will be removed from
     *      the filled area.
     */
    public function addPolygon($points, $invert = false);

    /**
     * Adds a triangle to the image.
     *
     * @param float $x The x-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $width The width of the bounding rectangle.
     * @param float $height The height of the bounding rectangle.
     * @param float $direction The direction of the 90 degree corner of 
     *      the triangle.
     * @param bool $invert If true the area of the triangle will be removed 
     *      from the filled area.
     */
    public function addTriangle($x, $y, $width, $height, $direction, $invert = false);

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
    public function addRhombus($x, $y, $width, $height, $invert = false);
    
    /**
     * Gets the output from the renderer.
     *
     * @return string
     */
    public function getData();
}
