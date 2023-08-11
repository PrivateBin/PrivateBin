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

namespace Jdenticon\Shapes;

/**
 * Represents a category of shapes that can be rendered in an icon. These 
 * instances are not hash specific.
 */
class ShapeCategory
{
    /**
     * The index of the hash octet determining the color of shapes in this 
     * category.
     *
     * @var int
     */
    public $colorIndex;

    /**
     * A list of possible shape definitions in this category.
     *
     * @var array(function(
     *      \Jdenticon\Rendering\RendererInterface $renderer, 
     *      \Jdenticon\Shapes\ShapePosition $cell, 
     *      int $index))
     */
    public $shapes;

    /**
     * The index of the hash octet determining which of the shape definitions 
     * that will be used for a particular hash.
     *
     * @var int
     */
    public $shapeIndex;

    /**
     * The index of the hash octet determining the rotation index of the shape 
     * in the first position.
     *
     * @var int
     */
    public $rotationIndex;

    /**
     * The positions in which the shapes of this category will be rendered.
     *
     * @var array(int)
     */
    public $positions;
    
    public function __construct(
        $colorIndex, array $shapes, $shapeIndex, $rotationIndex, array $positions)
    {
        $this->colorIndex = $colorIndex;
        $this->shapes = $shapes;
        $this->shapeIndex = $shapeIndex;
        $this->rotationIndex = $rotationIndex;
        $this->positions = $positions;
    }
}

