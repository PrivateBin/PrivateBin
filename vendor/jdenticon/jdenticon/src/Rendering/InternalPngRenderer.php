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

use Jdenticon\Canvas\Canvas;
use Jdenticon\Canvas\CanvasContext;
use Jdenticon\Color;

/**
 * Renders icons as PNG using the internal vector rasterizer.
 */
class InternalPngRenderer extends AbstractRenderer
{
    private Canvas $canvas;
    private CanvasContext $ctx;

    /**
     * Creates an instance of the class ImagickRenderer.
     *
     * @param int $width  The width of the icon in pixels.
     * @param int $height  The height of the icon in pixels.
     */
    public function __construct(int $width, int $height)
    {
        parent::__construct();
        $this->canvas = new Canvas($width, $height);
        $this->ctx = $this->canvas->getContext();
    }
    
    /**
     * Gets the MIME type of the renderer output.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return 'image/png';
    }

    /**
     * Adds a circle without translating its coordinates.
     *
     * @param float $x  The x-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $y  The y-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param float $size  The size of the bounding rectangle.
     * @param bool $counterClockwise  If true the circle will be drawn 
     *      counter clockwise.
     */
    protected function addCircleNoTransform(float $x, float $y, float $size, bool $counterClockwise): void
    {
        $radius = $size / 2;
        $this->ctx->moveTo($x + $size, $y + $radius);
        $this->ctx->arc(
            $x + $radius, $y + $radius, 
            $radius, 0, M_PI * 2, 
            $counterClockwise);
        $this->ctx->closePath();
    }

    /**
     * Adds a polygon without translating its coordinates.
     *
     * @param array<Point> $points  An array of the points that the polygon consists of.
     */
    protected function addPolygonNoTransform(array $points): void
    {
        $pointCount = count($points);
        $this->ctx->moveTo($points[0]->x, $points[0]->y);
        for ($i = 1; $i < $pointCount; $i++) {
            $this->ctx->lineTo($points[$i]->x, $points[$i]->y);
        }
        $this->ctx->closePath();
    }

    /**
     * Sets the background color of the icon.
     *
     * @param \Jdenticon\Color $color  The background color.
     */
    public function setBackgroundColor(Color $color): void
    {
        parent::setBackgroundColor($color);
        $this->canvas->backColor = $this->backgroundColor->toRgba();
    }

    /**
     * Begins a new shape. The shape should be ended with a call to endShape.
     *
     * @param \Jdenticon\Color $color  The color of the shape.
     */
    public function beginShape(Color $color): void
    {
        $this->ctx->fillStyle = $color->toRgba();
        $this->ctx->beginPath();
    }
    
    /**
     * Ends the currently drawn shape.
     */
    public function endShape(): void
    {
        $this->ctx->fill();
    }

    /**
     * Gets the output from the renderer.
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->canvas->toPng(['Software' => 'Jdenticon']);
    }
}
