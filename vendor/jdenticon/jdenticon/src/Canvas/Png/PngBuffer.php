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

class PngBuffer
{
    private $buffer = '';
    private $chunkPreviousBuffer = '';

    /**
     * Writes a string to the buffer.
     *
     * @param string $str  String to write.
     */
    public function writeString($str) 
    {
        $this->buffer .= $str;
    }
    
    /**
     * Writes a 32 bit unsigned int to the buffer in Big Endian format.
     *
     * @param integer $value  Value to write.
     */
    public function writeUInt32BE($value) 
    {
        $this->buffer .= pack('N', $value);
    }

    /**
     * Writes an 8 bit unsigned int to the buffer.
     *
     * @param integer $value  Value to write.
     */
    public function writeUInt8($value) 
    {
        $this->buffer .= pack('C', $value);
    }

    /**
     * Starts a new PNG chunk.
     *
     * @param string $type  Name of the chunk. Must contain exactly 4 
     *      ASCII characters.
     */
    public function startChunk($type) 
    {
        $this->chunkPreviousBuffer = $this->buffer;
        $this->buffer = $type;
    }

    /**
     * Closes the current PNG chunk.
     */
    public function endChunk() 
    {
        // Compute Crc32 for type + data
        $data = $this->buffer;
        $crc = crc32($data);

        $this->buffer = 
            $this->chunkPreviousBuffer .
            
            // Length
            pack('N', strlen($data) - 4) .

            // Content 
            $data .

            // Crc32
            pack('N', $crc);
    }

    /**
     * Gets a string containing the PNG encoded data.
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }
}
