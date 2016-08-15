<?php

namespace Identicon;

use Identicon\Generator\GdGenerator;
use Identicon\Generator\GeneratorInterface;

/**
 * @author Benjamin Laugueux <benjamin@yzalis.com>
 */
class Identicon
{
    /**
     * @var GeneratorInterface
     */
    private $generator;

    public function __construct($generator = null)
    {
        if (null === $generator) {
            $this->generator = new GdGenerator();
        } else {
            $this->generator = $generator;
        }

    }

    /**
     * Set the image generetor
     *
     * @param GeneratorInterface $generator
     *
     * @throws \Exception
     */
    public function setGenerator(GeneratorInterface $generator)
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * Display an Identicon image
     *
     * @param string  $string
     * @param integer $size
     * @param string  $color
     * @param string  $backgroundColor
     */
    public function displayImage($string, $size = 64, $color = null, $backgroundColor = null)
    {
        header("Content-Type: image/png");
        echo $this->getImageData($string, $size, $color, $backgroundColor);
    }

    /**
     * Get an Identicon PNG image data
     *
     * @param string  $string
     * @param integer $size
     * @param string  $color
     * @param string  $backgroundColor
     *
     * @return string
     */
    public function getImageData($string, $size = 64, $color = null, $backgroundColor = null)
    {
        return $this->generator->getImageBinaryData($string, $size, $color, $backgroundColor);
    }

    /**
     * Get an Identicon PNG image resource
     *
     * @param string  $string
     * @param integer $size
     * @param string  $color
     * @param string  $backgroundColor
     *
     * @return string
     */
    public function getImageResource($string, $size = 64, $color = null, $backgroundColor = null)
    {
        return $this->generator->getImageResource($string, $size, $color, $backgroundColor);
    }

    /**
     * Get an Identicon PNG image data as base 64 encoded
     *
     * @param string  $string
     * @param integer $size
     * @param string  $color
     * @param string  $backgroundColor
     *
     * @return string
     */
    public function getImageDataUri($string, $size = 64, $color = null, $backgroundColor = null)
    {
        return sprintf('data:image/png;base64,%s', base64_encode($this->getImageData($string, $size, $color, $backgroundColor)));
    }
}
