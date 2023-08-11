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

namespace Jdenticon\Canvas\Png;

use Jdenticon\Canvas\Png\PngPalette;
use Jdenticon\Canvas\Png\PngBuffer;
use Jdenticon\Canvas\ColorUtils;

class PngEncoder
{
    const GRAYSCALE = 0;
    const TRUE_COLOR = 2;
    const INDEXED_COLOR = 3;
    const GRAYSCALE_WITH_ALPHA = 4;
    const TRUE_COLOR_WITH_ALPHA = 6;

    private $buffer;
    
    public function __construct()
    {
        $this->buffer = new PngBuffer();
        $this->buffer->writeString("\x89\x50\x4e\x47\xd\xa\x1a\xa");
    }

    /**
     * Writes an IHDR chunk to the png data stream.
     *
     * @param int $width Image width in pixels.
     * @param int $height Image height in pixels.
     * @param int $colorType Color depth, speocfy one of the constants in 
     *      PngEncoder.
     */
    public function writeImageHeader($width, $height, $colorType) 
    {
        $this->buffer->startChunk("IHDR");
        $this->buffer->writeUInt32BE($width);
        $this->buffer->writeUInt32BE($height);
        $this->buffer->writeUInt8(8); // Bit depth
        $this->buffer->writeUInt8($colorType);
        $this->buffer->writeUInt8(0); // Compression
        $this->buffer->writeUInt8(0); // Filter
        $this->buffer->writeUInt8(0); // Interlace
        $this->buffer->endChunk();
    }

    /**
     * Writes a gAMA chunk to the png data stream.
     *
     * @param int $gamma Gamma value.
     */
    public function writeImageGamma($gamma = 45455) 
    {
        $this->buffer->startChunk("gAMA");
        $this->buffer->writeUInt32BE($gamma);
        $this->buffer->endChunk();
    }

    /**
     * Writes an IDAT chunk of truecolor encoded image data.
     *
     * @param array $colorRanges Image data on the format 
     *      array(count0, color0, count1, color1, ...)
     * @param int $width Image width in pixels.
     * @param int $height Image height in pixels.
     */
    public function writeTrueColorWithAlpha(
        array & $colorRanges, $width, $height) 
    {
        $this->buffer->startChunk("IDAT");

        $uncompressed = '';
        $count = -1;
        $x = 0;
        
        foreach ($colorRanges as $value) {
            if ($count === -1) {
                $count = $value;
            } else {
                if ($count !== 0) {
                    if ($x === $width) {
                        $x = 0;
                    }
                    if ($x === 0) {
                        $uncompressed .= pack('C', 0); // No filtering
                    }
                    
                    $uncompressed .= str_repeat(pack('N', $value), $count);
                    $x += $count;
                }
                
                $count = -1;
            }
        }

        $compressed = gzcompress($uncompressed, 2);
        $this->buffer->writeString($compressed);

        $this->buffer->endChunk();
    }

    /**
     * Writes an IDAT chunk of indexed image data.
     *
     * @param array $colorRanges Image data on the format 
     *      array(count0, color0, count1, color1, ...)
     * @param \Jdenticon\Canvas\Png\PngPalette $palette Palette containing the 
     *      indexed colors.
     * @param int $width Image width in pixels.
     * @param int $height Image height in pixels.
     */
    public function writeIndexed(
        array & $colorRanges, 
        PngPalette $palette, 
        $width, $height) 
    {
        $this->buffer->startChunk("IDAT");

        $uncompressed = '';

        $count = -1;
        $x = 0;

        foreach ($colorRanges as $value) {
            if ($count === -1) {
                $count = $value;
            } else {
                if ($count !== 0) {
                    if ($x === $width) {
                        $x = 0;
                    }
                    if ($x === 0) {
                        $uncompressed .= pack('C', 0); // No filtering
                    }
                    
                    $colorIndex = $palette->lookup[$value];
                    $uncompressed .= str_repeat(pack('C', $colorIndex), $count);
                    $x += $count;
                }
                
                $count = -1;
            }
        }

        $compressed = gzcompress($uncompressed, 2);
        $this->buffer->writeString($compressed);

        $this->buffer->endChunk();
    }

    /**
     * Writes a PLTE chunk containing the indexed colors.
     *
     * @param \Jdenticon\Canvas\Png\PngPalette $palette Palette containing the 
     *      indexed colors.
     */
    public function writePalette(PngPalette $palette) 
    {
        if ($palette && $palette->isValid) {
            $this->buffer->startChunk("PLTE");

            foreach ($palette->colors as $color) {
                $this->buffer->writeString(
                    pack('C', ($color >> 24) & 0xff) .
                    pack('C', ($color >> 16) & 0xff) .
                    pack('C', ($color >> 8) & 0xff));
            }

            $this->buffer->endChunk();
        }
    }

    /**
     * Writes a tRNS chunk containing the alpha values of indexed colors.
     *
     * @param \Jdenticon\Canvas\Png\PngPalette $palette Palette containing the 
     *      indexed colors.
     */
    public function writeTransparency(PngPalette $palette) 
    {
        if ($palette && $palette->isValid && $palette->hasAlphaChannel) {
            $this->buffer->startChunk("tRNS");
            
            $alpha = '';

            foreach ($palette->colors as $color) {
                $alpha .= pack('C', $color & 0xff);
            }
            
            $this->buffer->writeString($alpha);

            $this->buffer->endChunk();
        }
    }

    /**
     * Writes a tEXt chunk containing the specified strings.
     *
     * @param string $key Key, one of 
     *      {@link https://www.w3.org/TR/2003/REC-PNG-20031110/#11keywords}
     * @param string $value Value.
     */
    public function writeTextualData($key, $value) 
    {
        $this->buffer->startChunk("tEXt");
        $this->buffer->writeString($key);
        $this->buffer->writeUInt8(0);
        $this->buffer->writeString($value);
        $this->buffer->endChunk();
    }

    /**
     * Writes an IEND chunk to the png data stream.
     */
    public function writeImageEnd() 
    {
        $this->buffer->startChunk("IEND");
        $this->buffer->endChunk();
    }

    /**
     * Gets a binary string containing the PNG data.
     *
     * @return string
     */
    public function getBuffer() 
    {
        return $this->buffer->getBuffer();
    }
}
