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
     */
    public int $colorIndex;

    /**
     * A list of possible shape definitions in this category.
     *
     * @var array<callable(
     *      \Jdenticon\Rendering\RendererInterface $renderer, 
     *      int $cell, 
     *      int $index): void>
     */
    public array $shapes;

    /**
     * The index of the hash octet determining which of the shape definitions 
     * that will be used for a particular hash.
     */
    public int $shapeIndex;

    /**
     * The index of the hash octet determining the rotation index of the shape 
     * in the first position.
     */
    public ?int $rotationIndex;

    /**
     * The positions in which the shapes of this category will be rendered.
     *
     * @var array<int>
     */
    public array $positions;
    
    public function __construct(
        int $colorIndex, 
        array $shapes, 
        int $shapeIndex, 
        ?int $rotationIndex, 
        array $positions)
    {
        $this->colorIndex = $colorIndex;
        $this->shapes = $shapes;
        $this->shapeIndex = $shapeIndex;
        $this->rotationIndex = $rotationIndex;
        $this->positions = $positions;
    }
}

