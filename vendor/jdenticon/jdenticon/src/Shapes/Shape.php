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

use Jdenticon\Color;

/**
 * Represents a shape to be rendered in an icon. These instances are 
 * hash specific.
 */
class Shape
{
    /**
     * The shape definition to be used to render the shape.
     *
     * @var callable(
     *      \Jdenticon\Rendering\RendererInterface $renderer, 
     *      int $cell, 
     *      int $index): void
     */
    public $definition;

    /**
     * The fill color of the shape.
     */
    public Color $color;

    /**
     * The positions in which the shape will be rendered. Interleaved x and y cell coordinates.
     *
     * @var array<int>
     */
    public array $positions;

    /**
     * The rotation index of the icon in the first position.
     */
    public int $startRotationIndex;
    
    public function __construct(
        callable $definition, 
        Color $color, 
        array $positions, 
        int $startRotationIndex)
    {
        $this->definition = $definition;
        $this->color = $color;
        $this->positions = $positions;
        $this->startRotationIndex = $startRotationIndex;
    }
}
