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

namespace Jdenticon\Canvas\Rasterization;

use Jdenticon\Canvas\ColorUtils;
use Jdenticon\Canvas\Rasterization\LayerManager;
use Jdenticon\Canvas\Rasterization\SuperSampleBuffer;
use Jdenticon\Canvas\Rasterization\SuperSampleRange;
use Jdenticon\Canvas\Rasterization\EdgeSuperSampleIntersection;

class Rasterizer
{
    /**
     * A higher number of samples per pixel horizontally does not affect the
     * performance in the same way as SAMPLES_PER_PIXEL_Y, since the rasterizer
     * does not scan every subpixel horizontally.
     */
    const SAMPLES_PER_PIXEL_X = 10;

    /**
     * A higher number of samples vertically means lower performance, since
     * the rasterizer does a scan for every subpixel vertically.
     */
    const SAMPLES_PER_PIXEL_Y = 3;

    const SAMPLE_HEIGHT = 0.33333; //  1 / self::SAMPLES_PER_PIXEL_Y
    
    /**
     * Rasterizes the edges in the edge table to a list of color ranges. No 
     * range will span multiple scanlines.
     */
    public static function rasterize(& $colorData, $edgeTable, $width, $height) 
    {
        $edgeTable->sort();

        $superSampleBuffer = new SuperSampleBuffer(
            $width, self::SAMPLES_PER_PIXEL_X);

        $layers = array();
        $color = 0;
        
        // Keeps track of how many of the subpixellayers that are used for 
        // the currently rendered scanline. Until a range requiring 
        // supersampling is encountered only a single layer is needed.
        $usedLayers = 0;

        for ($i = 0; $i < self::SAMPLES_PER_PIXEL_Y; $i++) {
            $layers[] = new LayerManager();
        }

        for ($ey = 0; $ey < $height; $ey++) {
            $scanline = $edgeTable->getScanline($ey);
            if ($scanline === null) {
                $colorData[] = $width;
                $colorData[] = 0;
                continue;
            }
            
            $superSampleRanges = self::getSuperSampleRanges($scanline, $width);
            $superSampleRangeCount = count($superSampleRanges);
            
            foreach ($layers as $layer) {
                $layer->topLayer = null;
                $layer->color = ColorUtils::TRANSPARENT;
            }
            
            $usedLayers = 1;
            
            if ($superSampleRanges[0]->fromX) {
                $colorData[] = $superSampleRanges[0]->fromX;
                $colorData[] = 0;
            }
 
            for (
                $rangeIndex = 0; 
                $rangeIndex < $superSampleRangeCount; 
                $rangeIndex++
            ) {
                $superSampleRange = $superSampleRanges[$rangeIndex];
                $edge = $superSampleRange->edges[0];
                
                // If there is exactly one edge in the supersample range, and it
                // is crossing the entire scanline, we can perform the 
                // antialiasing by integrating the edge function.
                if (!isset($superSampleRange->edges[1]) && (
                        $edge->y0 <= $ey     && $edge->y1 >= $ey + 1 ||
                        $edge->y0 >= $ey + 1 && $edge->y1 <= $ey
                    )) {
                    // Determine the lower and upper x value where the edge 
                    // intersects the scanline.
                    $xey = $edge->intersection($ey);
                    $xey1 = $edge->intersection($ey + 1);
                    
                    if ($xey < $xey1) {
                        $x0 = $xey;
                        $x1 = $xey1;
                    } else {
                        $x0 = $xey1;
                        $x1 = $xey;
                    }
                    
                    $rangeWidth = $x1 - $x0;
                    
                    if ($usedLayers === 1) {
                        $subScanlineLayers = $layers[0];
                        $fromColor = $subScanlineLayers->color;
                        $subScanlineLayers->add($edge);
                        $toColor = $subScanlineLayers->color;
                    } else {
                        $fromColorR = 0; 
                        $fromColorG = 0; 
                        $fromColorB = 0; 
                        $fromColorA = 0;
                        $toColorR = 0; 
                        $toColorG = 0; 
                        $toColorB = 0; 
                        $toColorA = 0;

                        // Compute the average color of all subpixel layers 
                        // before and after the edge intersection.
                        // The calculation is inlined for increased performance.
                        for ($i = 0; $i < $usedLayers; $i++) {
                            $subScanlineLayers = $layers[$i];
                            
                            // Add to average from-color
                            $color = $subScanlineLayers->color;
                            $alpha = $color & 0xff;
                            if ($alpha > 0) {
                                $fromColorA += $alpha;
                                $fromColorR += (($color >> 24) & 0xff) * $alpha;
                                $fromColorG += (($color >> 16) & 0xff) * $alpha;
                                $fromColorB += (($color >> 8) & 0xff) * $alpha;
                            }
                            
                            // Add the new layer
                            $subScanlineLayers->add($edge);
                            
                            // Add to average to-color
                            $color = $subScanlineLayers->color;
                            $alpha = $color & 0xff;
                            if ($alpha > 0) {
                                $toColorA += $alpha;
                                $toColorR += (($color >> 24) & 0xff) * $alpha;
                                $toColorG += (($color >> 16) & 0xff) * $alpha;
                                $toColorB += (($color >> 8) & 0xff) * $alpha;
                            }
                        }

                        $fromColor = $fromColorA === 0 ? 0 : ColorUtils::from(
                            (int)($fromColorA / $usedLayers),
                            (int)($fromColorR / $fromColorA),
                            (int)($fromColorG / $fromColorA),
                            (int)($fromColorB / $fromColorA));
                            
                        $toColor = $toColorA === 0 ? 0 : ColorUtils::from(
                            (int)($toColorA / $usedLayers),
                            (int)($toColorR / $toColorA),
                            (int)($toColorG / $toColorA),
                            (int)($toColorB / $toColorA));
                    }
                    
                    // Render pixels
                    for (
                        $x = $superSampleRange->fromX; 
                        $x < $superSampleRange->toXExcl; 
                        $x++
                    ) {
                        if ($x0 >= $x + 1) {
                            // Pixel not covered
                            $colorData[] = 1;
                            $colorData[] = $fromColor;
                            continue;
                        }

                        if ($x1 <= $x) {
                            // Pixel fully covered
                            $colorData[] = 1;
                            $colorData[] = $toColor;
                            continue;
                        }
                        
                        // toColor coverage in the range [0.0, 1.0]
                        // Initialize to the fully covered range of the pixel.
                        $coverage = $x1 < $x + 1 ? $x + 1 - $x1 : 0;

                        // Compute integral for non-vertical edges
                        if ($rangeWidth > 0.001) {
                            // Range to integrate
                            $integralFrom = $x0 > $x ? $x0 : $x;;
                            $integralTo = $x1 < $x + 1 ? $x1 : $x + 1;

                            $coverage += 
                                (
                                    (
                                        $integralTo * $integralTo - 
                                        $integralFrom * $integralFrom
                                    ) / 2 +
                                    $x0 * ($integralFrom - $integralTo)
                                ) / $rangeWidth;
                        }
                        
                        $colorData[] = 1;
                        $colorData[] = ColorUtils::mix(
                            $fromColor, $toColor, $coverage);
                    }
                    
                    $color = $toColor;
                    
                } // /simplified antialiasing
                else {
                    // Super sampling 
                    $y = $ey + self::SAMPLE_HEIGHT / 2;
                    
                    // Ensure all subpixel layers are initialized
                    while ($usedLayers < self::SAMPLES_PER_PIXEL_Y) {
                        $layers[0]->copyTo($layers[$usedLayers]);
                        $usedLayers++;
                    }
                    
                    foreach ($layers as $subScanlineLayers) {
                        $color = $subScanlineLayers->color;

                        $intersections = self::getIntersections(
                            $superSampleRange->edges, $y);

                        foreach ($intersections as $intersection) {
                            $superSampleBuffer->add($color, 
                                $intersection->x - $superSampleRange->fromX);
                            $subScanlineLayers->add($intersection->edge);
                            $color = $subScanlineLayers->color;
                        }

                        $superSampleBuffer->add(
                            $color, $superSampleRange->width + 1);
                        $superSampleBuffer->rewind();

                        $y += self::SAMPLE_HEIGHT;
                    } // /subpixel

                    // Blend subpixels
                    $color = $superSampleBuffer->colorAt(
                        $superSampleRange->width);
                    $superSampleBuffer->emptyTo(
                        $colorData, $superSampleRange->width);
                    
                    //$color = end($colorData);
                } // /super sampling
                
                // Forward last color
                if ($rangeIndex + 1 < $superSampleRangeCount) {
                    $count = 
                        $superSampleRanges[$rangeIndex + 1]->fromX - 
                        $superSampleRange->toXExcl;
                        
                    if ($count > 0) {
                        $colorData[] = $count;
                        $colorData[] = $color;
                    }
                } else {
                    $count = $width - $superSampleRange->toXExcl;
                    if ($count > 0) {
                        $colorData[] = $count;
                        $colorData[] = $color;
                    }
                }
            } // /range
        }

        return $colorData;
    }
    
    private static function intersection_cmp($a, $b) 
    {
        if ($a->x < $b->x) {
            return -1;
        }
        
        if ($a->x > $b->x) {
            return 1;
        }
         
        return 0;
    }

    /**
     * Determines what edges that intersect a horizontal line with the specified 
     * y coordinate. For each intersecting edge the intersecting x coordinate is 
     * returned.
     *
     * @param array $edges Array of edges in the current scanline.
     * @param int $y Y coordinate of the current scanline.
     * @return array Array containing EdgeSuperSampleIntersection. Objects
     * are sorted ascending by x coordinate.
     */
    private static function getIntersections($edges, $y) 
    {
        $intersections = array();

        foreach ($edges as $edge) {
            if ($edge->y0 < $y && $edge->y1 >= $y ||
                $edge->y0 >= $y && $edge->y1 < $y
            ) {
                $x = $edge->x0 +
                    ($edge->x1 - $edge->x0) * ($y - $edge->y0) /
                    ($edge->y1 - $edge->y0);

                $intersections[] = new EdgeSuperSampleIntersection($x, $edge);
            }
        }

        usort($intersections, array(
            'Jdenticon\\Canvas\\Rasterization\\Rasterizer', 
            'intersection_cmp'));
        
        return $intersections;
    }

    /**
     * Determines what ranges of a scanline that needs to be supersampled.
     *
     * @param array $scanline  Array of edges in the current scanline.
     * @return array  Array of SuperSampleRange.
     */
    private static function getSuperSampleRanges(&$scanline, $width) 
    {
        $superSampleRanges = array();

        $rangeIndex = 0;
        $scanlineCount = count($scanline);

        while ($rangeIndex < $scanlineCount) {
            $range = $scanline[$rangeIndex];
            
            if ($range->fromX >= $width) {
                break;
            }
            
            $superSampleRange = new SuperSampleRange(
                $range->fromX,
                $range->fromX + $range->width
            );
            $superSampleRange->edges[] = $range->edge;
            
            $rangeIndex++;

            for ($i = $rangeIndex; $i < $scanlineCount; $i++) {
                $range = $scanline[$i];
                
                if ($range->fromX < $superSampleRange->toXExcl) {
                    $superSampleRange->toXExcl = max(
                        $superSampleRange->toXExcl, 
                        $range->fromX + $range->width);
                    $superSampleRange->edges[] = $range->edge;
                    $rangeIndex++;
                } else {
                    break;
                }
            }

            $superSampleRange->toXExcl = min($superSampleRange->toXExcl, $width);
            $superSampleRange->width = 
                $superSampleRange->toXExcl - $superSampleRange->fromX;
            
            $superSampleRanges[] = $superSampleRange;
        }

        return $superSampleRanges;
    }
}

