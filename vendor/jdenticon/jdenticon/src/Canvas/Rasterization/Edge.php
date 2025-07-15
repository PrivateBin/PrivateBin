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

class Edge
{
    public int $polygonId;
    public float $x0;
    public float $x1;
    public float $y0;
    public float $y1;
    public int $color;
    public string $windingRule;
 
    public function __construct(
        int $polygonId, 
        float $x0, 
        float $y0, 
        float $x1, 
        float $y1, 
        int $color, 
        string $windingRule = "nonzero") 
    {
        $this->polygonId = $polygonId;
        $this->x0 = $x0;
        $this->x1 = $x1;
        $this->y0 = $y0;
        $this->y1 = $y1;
        $this->color = $color;
        $this->windingRule = $windingRule;
    }

    public function intersection(float $y): float
    {
        $dx = 
            ($this->x1 - $this->x0) * ($this->y0 - $y) / 
            ($this->y0 - $this->y1);
        return $this->x0 + $dx;
    }
}

