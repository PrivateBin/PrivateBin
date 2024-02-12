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

class EdgeIntersection
{
    public $fromX;
    public $width;
    public $edge;
 
    public function __construct($fromX, $width, $edge) 
    {
        $this->fromX = $fromX;
        $this->width = $width;
        $this->edge = $edge;
    }
}

