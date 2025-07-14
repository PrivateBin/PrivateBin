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

use Jdenticon\Color;
use Jdenticon\Rendering\Transform;

/**
 * Base class for rendering shapes in an identicon. Implement this class to e.g. 
 * support a new file format that is not natively supported by Jdenticon. To 
 * invoke the new Renderer, pass the renderer as an argument to the 
 * {@see \Jdenticon\Identicon::Draw} method.
 */
abstract class AbstractRenderer implements RendererInterface
{
    private Transform $transform;
    protected ?Color $backgroundColor = null;

    public function __construct()
    {
        $this->transform = Transform::getEmpty();
    }
    
    /**
     * Sets the current transform that will be applied on all coordinates before 
     * being rendered to the target image.
     *
     * @param \Jdenticon\Rendering\Transform|null $transform The transform to set. 
     *      If NULL is specified any existing transform is removed.
     */
    public function setTransform(?Transform $transform): void
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
    public function getTransform(): Transform
    {
        return $this->transform;
    }
    
    /**
     * Adds a polygon without translating its coordinates.
     *
     * @param array<\Jdenticon\Rendering\Point> $points An array of the points that the polygon consists of.
     */
    abstract protected function addPolygonNoTransform(array $points): void;

    /**
     * Adds a circle without translating its coordinates.
     *
     * @param float $x The x-coordinate of the bounding rectangle upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle upper-left corner.
     * @param float $size The size of the bounding rectangle.
     * @param bool $counterClockwise If true the circle will be drawn counter clockwise.
     */
    abstract protected function addCircleNoTransform(float $x, float $y, float $size, bool $counterClockwise): void;

    /**
     * Sets the background color of the image.
     *
     * @param \Jdenticon\Color $color The image background color.
     */
    public function setBackgroundColor(Color $color): void
    {
        $this->backgroundColor = $color;
    }
    
    /**
     * Gets the background color of the image.
     *
     * @return \Jdenticon\Color
     */
    public function getBackgroundColor(): Color
    {
        if ($this->backgroundColor === null) {
            $this->backgroundColor = Color::fromRgb(0, 0, 0, 0);
        }
        return $this->backgroundColor;
    }
    
    /**
     * @param array<Point> $points
     */
    private function addPolygonCore(array $points, bool $invert): void
    {
        $transformedPoints = [];
        foreach ($points as $point) {
            $transformedPoints[] = 
                $this->transform->transformPoint($point->x, $point->y);
        }
        
        if ($invert) {
            $transformedPoints = array_reverse($transformedPoints);
        }

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
    public function addRectangle(float $x, float $y, float $width, float $height, bool $invert = false): void
    {
        $this->addPolygonCore([
            new Point($x, $y),
            new Point($x + $width, $y),
            new Point($x + $width, $y + $height),
            new Point($x, $y + $height),
        ], $invert);
    }

    /**
     * Adds a circle to the image.
     *
     * @param float $x The x-coordinate of the bounding rectangle upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle upper-left corner.
     * @param float $size The size of the bounding rectangle.
     * @param bool $invert If true the area of the circle will be removed from the filled area.
     */
    public function addCircle(float $x, float $y, float $size, bool $invert = false): void
    {
        $northWest = $this->transform->transformPoint($x, $y, $size, $size);
        $this->addCircleNoTransform($northWest->x, $northWest->y, $size, $invert);
    }

    /**
     * Adds a polygon to the image.
     *
     * @param array<Point> $points Array of points that the polygon consists of.
     * @param bool $invert If true the area of the polygon will be removed from the filled area.
     */
    public function addPolygon(array $points, bool $invert = false): void
    {
        $this->addPolygonCore($points, $invert);
    }

    /**
     * Adds a triangle to the image.
     *
     * @param float $x The x-coordinate of the bounding rectangle upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle upper-left corner.
     * @param float $width The width of the bounding rectangle.
     * @param float $height The height of the bounding rectangle.
     * @param int $direction The direction of the 90-degree corner of the triangle.
     *     Value of {@link \Jdenticon\Rendering\TriageDirection}
     * @param bool $invert If true the area of the triangle will be removed from the filled area.
     */
    public function addTriangle(float $x, float $y, float $width, float $height, int $direction, bool $invert = false): void
    {
        $points = [
            new Point($x + $width, $y),
            new Point($x + $width, $y + $height),
            new Point($x, $y + $height),
            new Point($x, $y)
        ];

        array_splice($points, $direction, 1);
        
        $this->addPolygonCore($points, $invert);
    }

    /**
     * Adds a rhombus to the image.
     *
     * @param float $x The x-coordinate of the bounding rectangle upper-left corner.
     * @param float $y The y-coordinate of the bounding rectangle upper-left corner.
     * @param float $width The width of the bounding rectangle.
     * @param float $height The height of the bounding rectangle.
     * @param bool $invert If true the area of the rhombus will be removed from the filled area.
     */
    public function addRhombus(float $x, float $y, float $width, float $height, bool $invert = false): void
    {
        $this->addPolygonCore([
            new Point($x + $width / 2, $y),
            new Point($x + $width, $y + $height / 2),
            new Point($x + $width / 2, $y + $height),
            new Point($x, $y + $height / 2),
        ], $invert);
    }
}
