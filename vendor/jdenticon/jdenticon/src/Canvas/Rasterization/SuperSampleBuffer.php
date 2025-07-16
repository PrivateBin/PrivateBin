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

namespace Jdenticon\Canvas\Rasterization;

use Jdenticon\Canvas\ColorUtils;

class SuperSampleBuffer
{
    const IDX_COUNT = 0;
    const IDX_A = 1;
    const IDX_R = 2;
    const IDX_G = 3;
    const IDX_B = 4;

    private array $samples;
    private int $samplesPerPixel;

    private int $pixelOffset;
    private int $subPixelOffset;

    private int $width;
    private int $used;

    /**
     * Creates a color buffer keeping an average color out of several
     * color samples per pixel.
     *
     * @param int $width  Width of the buffer in pixels.
     * @param int $samplesPerPixel  Number of samples to keep per pixel.
     */
    public function __construct(int $width, int $samplesPerPixel) 
    {
        $this->samples = [];
        $this->samplesPerPixel = $samplesPerPixel;

        $this->pixelOffset = 0;
        $this->subPixelOffset = 0;
        
        $this->width = $width;
        $this->used = -1;
    }

    /**
     * Rewinds the cursor to the beginning of the buffer.
     */
    public function rewind(): void
    {
        $this->pixelOffset = 0;
        $this->subPixelOffset = 0;
    }

    /**
     * Clears the samples in this buffer.
     */
    public function clear(): void
    {
        $this->pixelOffset = 0;
        $this->subPixelOffset = 0;
        $this->used = -1;
    }

    /**
     * Writes the average color of each pixel to a specified color array.
     *
     * @param array<int> $colorData The average colors will be written to this 
     *      color array.
     * @param int $count  Number of pixels to write.
     */
    public function emptyTo(array &$colorData, int $count): void
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
     * @param int $index The index of the pixel.
     * @return int
     */
    public function colorAt(int $index): int
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
     * @param int $count Number of samples of the color to be added to 
     *      the buffer.
     * @param int $a Alpha value.
     * @param int $r Red value.
     * @param int $g Green value.
     * @param int $b Blue value.
     */
    private function _add(int $count, int $a, int $r, int $g, int $b): void
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
     * @param int $color Color to write.
     * @param float $untilX Samples of the color will be added to the buffer until 
     *      the cursor reaches this coordinate.
     */
    public function add(int $color, float $untilX): void
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