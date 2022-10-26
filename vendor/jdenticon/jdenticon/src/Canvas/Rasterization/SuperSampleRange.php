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

class SuperSampleRange
{
    public $fromX;
    public $toXExcl;
    public $edges;
    public $width;
    
    public function __construct($fromX, $toXExcl) 
    {
        $this->fromX = $fromX;
        $this->toXExcl = $toXExcl;
        $this->edges = array();
    }
}