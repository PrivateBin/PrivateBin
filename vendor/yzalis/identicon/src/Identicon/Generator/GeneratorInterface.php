<?php

namespace Identicon\Generator;

/**
 * @author Benjamin Laugueux <benjamin@yzalis.com>
 */
interface GeneratorInterface
{
    /**
     *
     *
     * @param  string       $string
     * @param  integer      $size
     * @param  array|string $color
     * @param  array|string $backgroundColor
     *
     * @return mixed
     */
    function getImageBinaryData($string, $size = null, $color = null, $backgroundColor = null);

    /**
     *
     *
     * @param  string       $string
     * @param  integer      $size
     * @param  array|string $color
     * @param  array|string $backgroundColor
     *
     * @return string
     */
    function getImageResource($string, $size = null, $color = null, $backgroundColor = null);
}
