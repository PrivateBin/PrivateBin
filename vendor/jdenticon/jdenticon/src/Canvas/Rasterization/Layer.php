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

class Layer
{
    public $polygonId;
    public $color;
    public $winding;
    public $windingRule;
    
    public $nextLayer;
    
    public function __construct($polygonId, $color, $winding, $windingRule)
    {
        $this->polygonId = $polygonId;
        $this->color = $color;
        $this->winding = $winding;
        $this->windingRule = $windingRule;
    }
}
