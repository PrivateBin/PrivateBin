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

class EdgeTable
{
    private $scanlines;
    private $nextPolygonId;
    private $width;
    private $height;

    /**
     * Keeps a list of edges per scanline.
     *
     * @param integer $width  Clipping width.
     * @param integer $height  Clipping height.
     */
    public function __construct($width, $height) 
    {
        $this->width = $width;
        $this->height = $height;
        $this->clear();
    }

    /**
     * Sorts the edges of each scanline in ascending x coordinates.
     */
    public function clear() 
    {
        $this->scanlines = array();
        $this->nextPolygonId = 1;
    }

    /**
     * Gets an id for the next polygon.
     *
     * @return int
     */
    public function getNextPolygonId() 
    {
        return $this->nextPolygonId++;
    }

    /**
     * Gets the scaline for the specified Y coordinate, or NULL if there are 
     * no edges for the specified Y coordinate.
     *
     * @return array|null.
     */
    public function getScanline($y)
    {
        return isset($this->scanlines[$y]) ? $this->scanlines[$y] : null;
    }

    /**
     * Adds an edge to the table.
     *
     * @param \Jdenticon\Canvas\Rasterization\Edge $edge
     */
    public function add(\Jdenticon\Canvas\Rasterization\Edge $edge) 
    {
        $minY = 0;
        $maxY = 0;

        if ($edge->y0 == $edge->y1) {
            // Skip horizontal lines
            return;
        } elseif ($edge->y0 < $edge->y1) {
            $minY = (int)($edge->y0);
            $maxY = (int)($edge->y1 + 0.996 /* 1/255 */);
        } else {
            $minY = (int)($edge->y1);
            $maxY = (int)($edge->y0 + 0.996 /* 1/255 */);
        }

        if ($maxY < 0 || $minY >= $this->height) {
            return;
        }

        if ($minY < 0) {
            $minY = 0;
        }
        if ($maxY > $this->height) {
            $maxY = $this->height;
        }
        
        if ($minY < $maxY) {
            $y = $minY;
            $x1 = $edge->intersection($y);

            while ($y < $maxY) {
                $x2 = $edge->intersection($y + 1);

                $fromX;
                $width;
                if ($x1 < $x2) {
                    $fromX = (int)($x1);
                    $width = (int)($x2 + 0.9999) - $fromX;
                } else {
                    $fromX = (int)($x2);
                    $width = (int)($x1 + 0.9999) - $fromX;
                }

                if ($fromX < 0) {
                    $width += $fromX;
                    $fromX = 0;

                    if ($width < 0) {
                        $width = 0;
                    }
                }

                if ($fromX < $this->width) {
                    if (!isset($this->scanlines[$y])) {
                        $this->scanlines[$y] = array();
                    }
                    
                    $this->scanlines[$y][] = new EdgeIntersection(
                        $fromX, $width, $edge);
                }

                $x1 = $x2;
                $y++;
            }
        }
    }

    private static function edge_cmp($x, $y)
    {
        if ($x->fromX < $y->fromX) {
            return -1;
        }
        if ($x->fromX > $y->fromX) {
            return 1;
        }
        return 0;
    }

    /**
     * Sorts the edges of each scanline in ascending x coordinates.
     */
    public function sort() 
    {
        foreach ($this->scanlines as $i => &$scanline) {
            usort($scanline, array(
                'Jdenticon\\Canvas\\Rasterization\\EdgeTable', 'edge_cmp'));
        }
    }
}