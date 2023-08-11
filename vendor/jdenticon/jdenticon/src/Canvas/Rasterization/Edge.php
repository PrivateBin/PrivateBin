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

class Edge
{
    public $polygonId;
    public $x0;
    public $x1;
    public $y0;
    public $y1;
    public $color;
    public $windingRule;
 
    public function __construct(
        $polygonId, $x0, $y0, $x1, $y1, $color, $windingRule = null) 
    {
        $this->polygonId = $polygonId;
        $this->x0 = $x0;
        $this->x1 = $x1;
        $this->y0 = $y0;
        $this->y1 = $y1;
        $this->color = $color;
        $this->windingRule = $windingRule;
    }

    public function intersection($y)
    {
        $dx = 
            ($this->x1 - $this->x0) * ($this->y0 - $y) / 
            ($this->y0 - $this->y1);
        return $this->x0 + $dx;
    }
}

