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

use Jdenticon\Canvas\Rasterization\Edge;
use Jdenticon\Canvas\Rasterization\EdgeTable;
use Jdenticon\Canvas\Rasterization\Rasterizer;
use Jdenticon\Canvas\Png\PngPalette;
use Jdenticon\Canvas\Png\PngEncoder;
use Jdenticon\Canvas\CanvasContext;
use Jdenticon\Canvas\ColorUtils;

class Canvas
{
    private $edges;

    /**
     * Creates a new canvas with the specified dimensions given in pixels.
     *
     * @param integer $width  Canvas width in pixels.
     * @param integer $height  Canvas height in pixels.
     */
    public function __construct($width, $height) 
    {
        $this->width = $width;
        $this->height = $height;
        $this->edges = new EdgeTable($width, $height);
    }

    /**
     * The width of the canvas in pixels.
     *
     * @var integer
     */
    public $width = 0;

    /**
     * The height of the canvas in pixels.
     *
     * @var integer
     */
    public $height = 0;

    /**
     * Specifies the background color. Allowed values are:
     * - 32 bit integers on the format 0xRRGGBBAA
     * - strings on the format #RGB
     * - strings on the format #RRGGBB
     * - strings on the format #RRGGBBAA
     *
     * @var integer|string
     */
    public $backColor = 0x00000000;

    /**
     * Gets a context used to draw polygons on this canvas.
     *
     * @returns \Jdenticon\Canvas\CanvasContext
     */
    public function getContext() 
    {
        return new CanvasContext($this, $this->edges);
    }

    /**
     * Renders the canvas as a PNG data stream.
     *
     * @param array $keywords  Keywords to be written to the PNG stream. 
     *       See https://www.w3.org/TR/PNG/#11keywords.
     * @returns string
     */
    public function toPng($keywords = array()) 
    {
        $colorRanges = array();
        
        Rasterizer::rasterize(
            $colorRanges, $this->edges, 
            $this->width, $this->height);
        
        $backColor = ColorUtils::parse($this->backColor);
        if (ColorUtils::alpha($backColor) > 0) {
            $isColor = false;
            
            foreach ($colorRanges as & $value) {
                if ($isColor) {
                    $value = ColorUtils::over($value, $backColor);
                    $isColor = false;
                } else {
                    $isColor = true;
                }
            }
            
            unset($value);
        }

        $palette = new PngPalette($colorRanges);
        $png = new PngEncoder();

        $png->writeImageHeader($this->width, $this->height, $palette->isValid ?
            PngEncoder::INDEXED_COLOR : PngEncoder::TRUE_COLOR_WITH_ALPHA);

        $png->writeImageGamma();
        
        foreach ($keywords as $key => $value) {
            $png->writeTextualData($key, $value);
        }
        
        if ($palette && $palette->isValid) {
            $png->writePalette($palette);
            $png->writeTransparency($palette);
            $png->writeIndexed($colorRanges, $palette, 
                $this->width, $this->height);
        } else {
            $png->writeTrueColorWithAlpha($colorRanges, 
                $this->width, $this->height);
        }

        $png->writeImageEnd();
        return $png->getBuffer();
    }
}