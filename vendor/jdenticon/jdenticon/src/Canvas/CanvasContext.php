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

namespace Jdenticon\Canvas;

use Jdenticon\Canvas\ColorUtils;
use Jdenticon\Canvas\CanvasState;
use Jdenticon\Canvas\Rasterization\EdgeTable;
use Jdenticon\Canvas\Rasterization\Edge;
use Jdenticon\Canvas\Matrix;

class CanvasContext
{
    private $savedStates = array();
    private $edges;
    private $transform;
    private $paths;
    private $canvas;

    /**
     * Creates a new canvas with the specified dimensions given in pixels.
     *
     * @param \Jdenticon\Canvas\Canvas $canvas The owner canvas.
     * @param array $edges The owner canvas' edge buffer.
     */
    public function __construct($canvas, &$edges) 
    {
        $this->edges = $edges;
        $this->canvas = $canvas;
        $this->beginPath();
        $this->resetTransform();
    }

    /**
     * Specifies the fill color that is used when the fill method is called. Allowed values are:
     * - 32 bit integers on the format 0xRRGGBBAA
     * - strings on the format #RGB
     * - strings on the format #RRGGBB
     * - strings on the format #RRGGBBAA
     *
     * @var integer|string
     */
    public $fillStyle = 0x000000ff;

    /**
     * Saves the current state to the state stack.
     */
    public function save() 
    {
        array_push($this->savedStates, array(
            'transform' => $this->transform,
            'fillStyle' => $this->fillStyle
        ));
    }

    /**
     * Restores the last saved state of the CanvasContext.
     */
    public function restore() 
    {
        $state = array_pop($this->savedStates);
        if ($state != NULL) {
            $this->transform = $state['transform'];
            $this->fillStyle = $state['fillStyle'];
        }
    }

    /**
     * Resets the internal path buffer and begins a new path.
     */
    public function resetTransform() 
    {
        $this->transform = new Matrix(1, 0, 0, 1, 0, 0);
    }

    /**
     * Multiplies the current transformation matrix with the specified values.
     */
    public function transform($a, $b, $c, $d, $e, $f) 
    {
        if (gettype($a) != 'integer' ||
            gettype($b) != 'integer' ||
            gettype($c) != 'integer' ||
            gettype($d) != 'integer' ||
            gettype($e) != 'integer' ||
            gettype($f) != 'integer'
        ) {
            return;
        }

        $this->transform = $this->transform->multiply($a, $b, $c, $d, $e, $f);
    }

    /**
     * Sets the transformation matrix to the specified matrix.
     */
    public function setTransform($a, $b, $c, $d, $e, $f) 
    {
        if (gettype($a) != 'integer' ||
            gettype($b) != 'integer' ||
            gettype($c) != 'integer' ||
            gettype($d) != 'integer' ||
            gettype($e) != 'integer' ||
            gettype($f) != 'integer'
        ) {
            return;
        }

        $this->transform = new Matrix($a, $b, $c, $d, $e, $f);
    }

    /**
     * Applies a translation transformation to the CanvasContext.
     *
     * @param float $x Distance to move in the horizontal direction in pixels.
     * @param float $y Distance to move in the vertical direction in pixels.
     */
    public function translate($x, $y) 
    {
        $this->transform = $this->transform->translate($x, $y);
    }

    /**
     * Applies a scale transformation to the CanvasContext.
     *
     * @param float $x Scale in the horizontal direction. 1 means no scale.
     * @param float $y Scale in the vertical direction. 1 means no scale.
     */
    public function scale($x, $y) 
    {
        $this->transform = $this->transform->scale($x, $y);
    }

    /**
     * Applies a rotation transformation to the canvas around its current origo.
     *
     * @param float $angle  Angle in radians measured clockwise from the 
     *      positive x axis.
     */
    public function rotate($angle) 
    {
        $this->transform = $this->transform->rotate($angle);
    }

    /**
     * Removes all existing subpaths and begins a new path.
     */
    public function beginPath() 
    {
        $this->paths = array();
    }

    /**
     * Starts a new subpath that begins in the same point as the start and end 
     * point of the previous one.
     */
    public function closePath() 
    {
        $pathsCount = count($this->paths);
        if ($pathsCount > 0) {
            $path = $this->paths[$pathsCount - 1];
            $pathCount = count($path);
            
            if ($pathCount > 2) {
                // Close path
                if ($path[0] != $path[$pathCount - 2] ||
                    $path[1] != $path[$pathCount - 1]
                ) {
                    $path[] = $path[0];
                    $path[] = $path[1];
                }

                // Begin a new path
                $this->paths[] = array($path[0], $path[1]);
            }
        }
    }

    /**
     * Begins a new subpath by moving the cursor to the specified position.
     *
     * @param float $x  X coordinate.
     * @param float $y  Y coordinate.
     */
    public function moveTo($x, $y) 
    {
        $p = $this->transform->multiplyPoint($x, $y);
        $this->paths[] = array($p->x, $p->y);
    }

    /**
     * Inserts an edge between the last and specified position.
     *
     * @param float $x  Target X coordinate.
     * @param float $y  Target Y coordinate.
     * @public
     */
    public function lineTo($x, $y) 
    {
        $pathsCount = count($this->paths);
        if ($pathsCount == 0) {
            $this->paths[] = array();
            $pathsCount++;
        }

        $p = $this->transform->multiplyPoint($x, $y);
        $path = &$this->paths[$pathsCount - 1];
        $path[] = $p->x;
        $path[] = $p->y;
    }

    /**
     * Adds an arc to the current path.
     *
     * @param float $x  X coordinate of the center of the arc.
     * @param float $y  Y coordinate of the center of the arc.
     * @param float $radius  Radius of the arc.
     * @param float $startAngle  The angle in radians at which the arc starts, 
     *       measured clockwise from the positive x axis.
     * @param float $endAngle  The angle in radians at which the arc end, 
     *       measured clockwise from the positive x axis.
     * @param boolean $anticlockwise  Specifies whether the arc will be drawn 
     *       counter clockwise. Default is clockwise.
     */
    public function arc($x, $y, $radius, $startAngle, $endAngle, $anticlockwise) 
    {
        $TARGET_CHORD_LENGTH_PIXELS = 3;
        
        $sectors = floor((M_PI * $radius * 2) / $TARGET_CHORD_LENGTH_PIXELS);
        if ($sectors < 9) {
            $sectors = 9;
        }
        
        $sectorAngle = M_PI * 2 / $sectors;

        if ($startAngle == $endAngle) {
            return;
        }

        if ($anticlockwise) {
            $sectorAngle = -$sectorAngle;

            if ($startAngle - $endAngle >= M_PI * 2) {
                $endAngle = $startAngle - M_PI * 2;
            } else {
                // Normalize end angle so that the sweep angle is in the range 
                // (0, -2PI]
                $endAngle += 
                    M_PI * 2 * ceil(($startAngle - $endAngle) / 
                    (M_PI * 2) - 1);
            }
        } else {
            if ($endAngle - $startAngle >= M_PI * 2) {
                $endAngle = $startAngle + M_PI * 2;
            } else {
                // Normalize end angle so that the sweep angle is in the range 
                // (0, 2PI]
                $endAngle -= 
                    M_PI * 2 * ceil(($endAngle - $startAngle) / 
                    (M_PI * 2) - 1);
            }
        }
        
        $dx;
        $dy;
        $sectors = ($endAngle - $startAngle) / $sectorAngle;

        $angle = $startAngle;
        
        for ($i = 0; $i < $sectors; $i++) {
            $dx = cos($angle) * $radius;
            $dy = sin($angle) * $radius;
            $this->lineTo($x + $dx, $y + $dy);
            $angle += $sectorAngle;
        }

        $dx = cos($endAngle) * $radius;
        $dy = sin($endAngle) * $radius;
        $this->lineTo($x + $dx, $y + $dy);
    }

    /**
     * Fills the specified rectangle with fully transparent black without 
     * affecting the current paths.
     *
     * @param float $x X coordinate of the left side of the rectangle.
     * @param float $y Y coordinate of the top of the rectangle.
     * @param float $width Width of the rectangle.
     * @param float $height Height of the rectangle.
     */
    public function clearRect($x, $y, $width, $height) 
    {
        $fullCanvas = false;

        if (!$this->transform->hasSkewing()) {
            // Check if the whole canvas is cleared
            $topLeft = $this->transform->multiplyPoint($x, $y);
            if ($topLeft->x <= 0 && $topLeft->y <= 0) {
                $bottomRight = $this->transform->multiplyPoint(
                    $x + $width, $y + $height);
                if ($bottomRight->x >= $this->canvas->width &&
                    $bottomRight->y >= $this->canvas->height
                ) {
                    $fullCanvas = true;
                }
            }
        }

        if ($fullCanvas) {
            $this->edges->clear();
        } else {
            $this->_fillRect(ColorUtils::FORCE_TRANSPARENT, 
                $x, $y, $width, $height);
        }
    }

    /**
     * Fills the specified rectangle without affecting the current paths.
     *
     * @param float $x X coordinate of the left side of the rectangle.
     * @param float $y Y coordinate of the top of the rectangle.
     * @param float $width Width of the rectangle.
     * @param float $height Height of the rectangle.
     */
    public function fillRect($x, $y, $width, $height) 
    {
        $fillColor = ColorUtils::parse($this->fillStyle);
        $this->_fillRect($fillColor, $x, $y, $width, $height);
    }

    private function _fillRect($fillColor, $x, $y, $width, $height) 
    {
        $polygonId = $this->edges->getNextPolygonId();
        
        $points = array(
            $this->transform->multiplyPoint($x, $y),
            $this->transform->multiplyPoint($x + $width, $y),
            $this->transform->multiplyPoint($x + $width, $y + $height),
            $this->transform->multiplyPoint($x, $y + $height),
            $this->transform->multiplyPoint($x, $y)
        );

        $pointsCount = count($points);
        for ($i = 1; $i < $pointsCount; $i++) {
            $this->edges->add(new Edge(
                $polygonId,
                $points[$i - 1]->x,
                $points[$i - 1]->y,
                $points[$i]->x,
                $points[$i]->y,
                $fillColor));
        }
    }

    /**
     * Fills the defined paths.
     *
     * @param string $windingRule  The winding rule to be used for determining
     *      which areas are covered by the current path. Valid values are 
     *      "evenodd" and "nonzero". Default is "nonzero".
     */
    public function fill($windingRule = "nonzero") 
    {
        $polygonId = $this->edges->getNextPolygonId();
        $fillColor = ColorUtils::parse($this->fillStyle);
        
        foreach ($this->paths as $points) {
            $pointsCount = count($points);
            if ($pointsCount <= 2) {
                // Nothing to fill
                continue;
            }

            for ($i = 2; $i < $pointsCount; $i += 2) {
                $this->edges->add(new Edge(
                    $polygonId,
                    $points[$i - 2],
                    $points[$i - 1],
                    $points[$i],
                    $points[$i + 1],
                    $fillColor,
                    $windingRule));
            }
            
            // Close path
            if ($points[0] != $points[$pointsCount - 2] ||
                $points[1] != $points[$pointsCount - 1]
            ) {
                $this->edges->add(new Edge(
                    $polygonId,
                    $points[$pointsCount - 2],
                    $points[$pointsCount - 1],
                    $points[0],
                    $points[1],
                    $fillColor,
                    $windingRule));
            }
        }
    }
}
