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

class Layer
{
    public int $polygonId;
    public int $color;
    public int $winding;
    public string $windingRule;
    public ?Layer $nextLayer = null;
    
    /**
     * Creates a new layer.
     * 
     * @param int $polygonId Unique id for this layer.
     * @param int $color Color on the format 0xRRGGBBAA.
     * @param int $winding Differential winding value, either 1 or -1.
     * @param string $windingRule Winding rule for the polygon, either "evenodd" or "nonzero".
     */
    public function __construct(int $polygonId, int $color, int $winding, string $windingRule)
    {
        $this->polygonId = $polygonId;
        $this->color = $color;
        $this->winding = $winding;
        $this->windingRule = $windingRule;
    }
}
