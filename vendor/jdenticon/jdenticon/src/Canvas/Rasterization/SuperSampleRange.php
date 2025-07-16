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

class SuperSampleRange
{
    public int $fromX;
    public int $toXExcl;
    /** @var array<Edge> */
    public array $edges;
    public int $width;
    
    public function __construct(int $fromX, int $toXExcl) 
    {
        $this->fromX = $fromX;
        $this->toXExcl = $toXExcl;
        $this->edges = [];
    }
}