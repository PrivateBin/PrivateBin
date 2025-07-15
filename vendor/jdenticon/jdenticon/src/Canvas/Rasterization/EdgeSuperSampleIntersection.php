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

class EdgeSuperSampleIntersection
{
    public float $x;
    public Edge $edge;
    
    public function __construct(float $x, Edge $edge) 
    {
        $this->x = $x;
        $this->edge = $edge;
    }
}