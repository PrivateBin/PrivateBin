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

class SuperSampleBuffer
{
    const IDX_COUNT = 0;
    const IDX_A = 1;
    const IDX_R = 2;
    const IDX_G = 3;
    const IDX_B = 4;

    /**
     * Creates a color buffer keeping an average color out of several
     * color samples per pixel.
     *
     * @param integer $width  Width of the buffer in pixels.
     * @param integer $samplesPerPixel  Number of samples to keep per pixel.
     */
    public function __construct($width, $samplesPerPixel) 
    {
        $this->samples = array();
        $this->samplesPerPixel = $samplesPerPixel;

        $this->pixelOffset = 0;
        $this->subPixelOffset = 0;
        
        $this->width = $width;
        $this->used = -1;
    }

    /**
     * Rewinds the cursor to the beginning of the buffer.
     */
    public function rewind() 
    {
        $this->pixelOffset = 0;
        $this->subPixelOffset = 0;
    }

    /**
     * Clears the samples in this buffer.
     */
    public function clear() 
    {
        $this->pixelOffset = 0;
        $this->subPixelOffset = 0;
        $this->used = -1;
    }

    /**
     * Writes the average color of each pixel to a specified color array.
     *
     * @param array $colorData The average colors will be written to this 
     *      color array.
     * @param integer $count  Number of pixels to write.
     */
    public function emptyTo(& $colorData, $count) 
    {
        for ($i = 0; $i < $count; $i++) {
            $sampleCount = $this->samples[$i * 5 + self::IDX_COUNT];
            $a = $this->samples[$i * 5 + self::IDX_A];
            $color = $sampleCount == 0 || $a == 0 ? 0 :
                ColorUtils::from(
                    (int)($a / $sampleCount),
                    (int)($this->samples[$i * 5 + self::IDX_R] / $a),
                    (int)($this->samples[$i * 5 + self::IDX_G] / $a),
                    (int)($this->samples[$i * 5 + self::IDX_B] / $a)
                );
            
            $colorData[] = 1;
            $colorData[] = $color;
        }
        
        $this->pixelOffset = 0;
        $this->subPixelOffset = 0;
        $this->used = -1;
    }
    
    /**
     * Gets the average color of the pixel at a specified index.
     *
     * @param integer $index The index of the pixel.
     * @return integer
     */
    public function colorAt($index) 
    {
        $sampleCount = $this->samples[$index * 5 + self::IDX_COUNT];
        $alphaSum = $this->samples[$index * 5 + self::IDX_A];
        return $sampleCount == 0 || $alphaSum == 0 ? 0 :
            ColorUtils::from(
                (int)($alphaSum / $sampleCount),
                (int)($this->samples[$index * 5 + self::IDX_R] / $alphaSum),
                (int)($this->samples[$index * 5 + self::IDX_G] / $alphaSum),
                (int)($this->samples[$index * 5 + self::IDX_B] / $alphaSum)
            );
    }

    /**
     * Adds a color to the current pixel in the buffer.
     *
     * @param integer $count Number of samples of the color to be added to 
     *      the buffer.
     */
    private function _add($count, $a, $r, $g, $b) 
    {
        if ($this->used < $this->pixelOffset) {
            $this->used = $this->pixelOffset;
            
            $this->samples[$this->pixelOffset * 5 + self::IDX_COUNT] = $count;
            $this->samples[$this->pixelOffset * 5 + self::IDX_A] = $a * $count;
            $this->samples[$this->pixelOffset * 5 + self::IDX_R] = $a * $r * $count;
            $this->samples[$this->pixelOffset * 5 + self::IDX_G] = $a * $g * $count;
            $this->samples[$this->pixelOffset * 5 + self::IDX_B] = $a * $b * $count;
        } else {
            $this->samples[$this->pixelOffset * 5 + self::IDX_COUNT] += $count;

            if ($a > 0) {
                $this->samples[$this->pixelOffset * 5 + self::IDX_A] += $a * $count;
                $this->samples[$this->pixelOffset * 5 + self::IDX_R] += $a * $r * $count;
                $this->samples[$this->pixelOffset * 5 + self::IDX_G] += $a * $g * $count;
                $this->samples[$this->pixelOffset * 5 + self::IDX_B] += $a * $b * $count;
            }
        }
    }
    
    /**
     * Adds a color to the buffer up until the specified x index.
     *
     * @param integer $color Color to write.
     * @param float $untilX Samples of the color will be added the buffer until 
     *      the cursor reaches this coordinate.
     */
    public function add($color, $untilX) 
    {
        $samplesLeft = 
            (int)($untilX * $this->samplesPerPixel) - 
            $this->subPixelOffset - 
            $this->pixelOffset * $this->samplesPerPixel;

        // ColorUtils methods inlined for performance reasons
        $a = ($color) & 0xff;
        $r = ($color >> 24) & 0xff;
        $g = ($color >> 16) & 0xff;
        $b = ($color >> 8) & 0xff;
        
        // First partial pixel
        if ($this->subPixelOffset > 0) {
            $samples = $this->samplesPerPixel - $this->subPixelOffset;
            if ($samples > $samplesLeft) {
                $samples = $samplesLeft;
            }
            $samplesLeft -= $samples;

            $this->_add($samples, $a, $r, $g, $b);

            $this->subPixelOffset += $samples;
            if ($this->subPixelOffset == $this->samplesPerPixel) {
                $this->subPixelOffset = 0;
                $this->pixelOffset++;
            }
        }

        // Full pixels
        $fullPixels = (int)($samplesLeft / $this->samplesPerPixel);
        if ($fullPixels > 0) {
            for ($i = 0; $i < $fullPixels; $i++) {
                $this->_add($this->samplesPerPixel, $a, $r, $g, $b);
                $this->pixelOffset++;
            }

            $samplesLeft -= $fullPixels * $this->samplesPerPixel;
        }

        // Last partial pixel
        if ($samplesLeft > 0) {
            $this->_add($samplesLeft, $a, $r, $g, $b);

            $this->subPixelOffset += $samplesLeft;

            if ($this->subPixelOffset == $this->samplesPerPixel) {
                $this->subPixelOffset = 0;
                $this->pixelOffset++;
            }
        }
    }
}