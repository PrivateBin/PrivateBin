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

use Jdenticon\Canvas\ColorUtils;
use Jdenticon\Canvas\Rasterization\Layer;
use Jdenticon\Canvas\Rasterization\Edge;

/**
 * Keeps track of the z-order of the currently rendered polygons,
 * and computes the final color from the stack of layers.
 */
class LayerManager
{
    public $topLayer;

    /**
     * The current visible color.
     * @var integer
     */
    public $color;

    public function __construct() 
    {
        $this->color = ColorUtils::TRANSPARENT;
    }

    /**
     * Copies all layers in this manager to another LayerManager.
     *
     * @param \Jdenticon\Canvas\Rasterization\LayerManager $other The 
     *      LayerManager to copy all layers to.
     */
    public function copyTo(LayerManager $other) 
    {
        $other->color = $this->color;
        
        $layer = $this->topLayer;
        $previousCopy = null;
        
        while ($layer !== null) {
            $copy = new Layer(
                $layer->polygonId,
                $layer->color,
                $layer->winding,
                $layer->windingRule
                );
            
            if ($previousCopy === null) {
                $other->topLayer = $copy;
            }
            else {
                $previousCopy->nextLayer = $copy;
            }
            
            $previousCopy = $copy;
            $layer = $layer->nextLayer;
        }
    }

    /**
     * Adds a layer for the specified edge. The z-order is defined by its id.
     *
     * @param \Jdenticon\Canvas\Rasterization\Edge edge
     */
    public function add(Edge $edge) 
    {
        $dwinding = $edge->y0 < $edge->y1 ? 1 : -1;
        
        $layer = $this->topLayer;
        $previousLayer = null;
        
        while ($layer !== null) {
            if ($layer->polygonId === $edge->polygonId) {
                $layer->winding += $dwinding;

                $inPath = $layer->windingRule == 'evenodd' ? 
                    ($layer->winding % 2 === 1) : ($layer->winding !== 0);

                if (!$inPath) {
                    // Remove layer
                    if ($previousLayer === null) {
                        $this->topLayer = $layer->nextLayer;
                    }
                    else {
                        $previousLayer->nextLayer = $layer->nextLayer;
                    }
                }
                break;
            } elseif ($layer->polygonId < $edge->polygonId) {
                // Insert here
                $newLayer = new Layer(
                    $edge->polygonId,
                    $edge->color,
                    $dwinding,
                    $edge->windingRule
                );
                $newLayer->nextLayer = $layer;
                
                if ($previousLayer === null) {
                    $this->topLayer = $newLayer;
                } else {
                    $previousLayer->nextLayer = $newLayer;
                }
                break;
            }
            
            $previousLayer = $layer;
            $layer = $layer->nextLayer;
        }
        
        if ($layer === null) {
            $newLayer = new Layer(
                $edge->polygonId,
                $edge->color,
                $dwinding,
                $edge->windingRule
            );
            
            if ($previousLayer === null) {
                $this->topLayer = $newLayer;
            } else {
                $previousLayer->nextLayer = $newLayer;
            }
        }

        // Update current color
        $color = ColorUtils::TRANSPARENT;
        $layer = $this->topLayer;
        
        while ($layer !== null && ($color & 0xff) < 255) {
            if ($layer->color === ColorUtils::FORCE_TRANSPARENT) {
                break;
            }
            
            $color = ColorUtils::over($layer->color, $color);
        }
        
        $this->color = $color;
    }
}
