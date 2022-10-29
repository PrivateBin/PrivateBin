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

use Jdenticon\Canvas\Point;

class Matrix
{
    private $a;
    private $b;
    private $c;
    private $d;
    private $e;
    private $f;

    /**
     * Creates a new transformation matrix.
     */
    public function __construct($a, $b, $c, $d, $e, $f) 
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
        $this->e = $e;
        $this->f = $f;
    }

    /**
     * Gets a value determining if this matrix has skewing values.
     *
     * @return boolean
     */
    public function hasSkewing() 
    {
        return $this->b || $this->c;
    }

    /**
     * Gets a value determining if this matrix has translation values.
     *
     * @return boolean
     */
    public function hasTranslation() 
    {
        return $this->e || $this->f;
    }

    /**
     * Gets a value determining if this matrix has scaling values.
     *
     * @return boolean
     */
    public function hasScaling() 
    {
        return $this->a != 1 || $this->d != 1;
    }

    /**
     * Returns a new matrix based on the current matrix multiplied with the 
     * specified matrix values.
     *
     * @return \Jdenticon\Canvas\Matrix
     */
    public function multiply($a, $b, $c, $d, $e, $f) 
    {
        return new Matrix(
            $this->a * $a + $this->c * $b,
            $this->b * $a + $this->d * $b,
            $this->a * $c + $this->c * $d,
            $this->b * $c + $this->d * $d,
            $this->a * $e + $this->c * $f + $this->e,
            $this->b * $e + $this->d * $f + $this->f
        );
    }

    /**
     * Multiplies the specified point with the current matrix and returns the 
     * resulting point.
     *
     * @param float $x  X coordinate.
     * @param float $y  Y coordinate.
     * @return \Jdenticon\Canvas\Point
     */
    public function multiplyPoint($x, $y) 
    {
        return new Point(
            $this->a * $x + $this->c * $y + $this->e,
            $this->b * $x + $this->d * $y + $this->f
        );
    }

    /**
     * Returns a new matrix based on the current matrix with a rotation 
     * transformation applied.
     *
     * @param float $angle  Rotation angle in radians.
     * @return \Jdenticon\Canvas\Matrix
     */
    public function rotate($angle) 
    {
        $sin = sin($angle);
        $cos = cos($angle);
        return $this->multiply($cos, $sin, -$sin, $cos, 0, 0);
    }

    /**
     * Returns a new matrix based on the current matrix with a translation 
     * transformation applied.
     *
     * @param float $x  Horizontal move distance.
     * @param float $y  Vertical move distance.
     * @return \Jdenticon\Canvas\Matrix
     */
    public function translate($x, $y) 
    {
        return $this->multiply(1, 0, 0, 1, $x, $y);
    }

    /**
     * Returns a new matrix based on the current matrix with a scaling 
     * transformation applied.
     *
     * @param float $x  Horizontal scale.
     * @param float $y  Vertical scale.
     * @return \Jdenticon\Canvas\Matrix
     */
    public function scale($x, $y) 
    {
        return $this->multiply($x, 0, 0, $y, 0, 0);
    }
}