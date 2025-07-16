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

/**
 * Specifies in what direction the 90 degree angle of a triangle is pointing.
 */
class TriangleDirection
{
    /**
     * The 90 degree angle is pointing to South West.
     */
    public const SOUTH_WEST = 0;
    /**
     * The 90 degree angle is pointing to North West.
     */
    public const NORTH_WEST = 1;
    /**
     * The 90 degree angle is pointing to North East.
     */ 
    public const NORTH_EAST = 2;
    /**
     * The 90 degree angle is pointing to South East.
     */
    public const SOUTH_EAST = 3;
}
