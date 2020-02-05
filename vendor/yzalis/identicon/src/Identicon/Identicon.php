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
     * @var \Identicon\Generator\GeneratorInterface
     */
    private $generator;

    /**
     * Identicon constructor.
     *
     * @param \Identicon\Generator\GeneratorInterface|null $generator
     */
    public function __construct($generator = null)
    {
        if (null === $generator) {
            $this->generator = new GdGenerator();
        } else {
            $this->generator = $generator;
        }
    }

    /**
     * Set the image generator.
     *
     * @param \Identicon\Generator\GeneratorInterface $generator
     *
     * @return $this
     */
    public function setGenerator(GeneratorInterface $generator)
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * Display an Identicon image.
     *
     * @param string       $string
     * @param int          $size
     * @param string|array $color
     * @param string       $backgroundColor
     */
    public function displayImage($string, $size = 64, $color = null, $backgroundColor = null)
    {
        header('Content-Type: '.$this->generator->getMimeType());
        echo $this->getImageData($string, $size, $color, $backgroundColor);
    }

    /**
     * Get an Identicon PNG image data.
     *
     * @param string       $string
     * @param int          $size
     * @param string|array $color
     * @param string       $backgroundColor
     *
     * @return string
     */
    public function getImageData($string, $size = 64, $color = null, $backgroundColor = null)
    {
        return $this->generator->getImageBinaryData($string, $size, $color, $backgroundColor);
    }

    /**
     * Get an Identicon PNG image resource.
     *
     * @param string       $string
     * @param int          $size
     * @param string|array $color
     * @param string       $backgroundColor
     *
     * @return string
     */
    public function getImageResource($string, $size = 64, $color = null, $backgroundColor = null)
    {
        return $this->generator->getImageResource($string, $size, $color, $backgroundColor);
    }

    /**
     * Get an Identicon PNG image data as base 64 encoded.
     *
     * @param string       $string
     * @param int          $size
     * @param string|array $color
     * @param string       $backgroundColor
     *
     * @return string
     */
    public function getImageDataUri($string, $size = 64, $color = null, $backgroundColor = null)
    {
        return sprintf('data:%s;base64,%s', $this->generator->getMimeType(), base64_encode($this->getImageData($string, $size, $color, $backgroundColor)));
    }

	/**
	 * Get the color of the Identicon
     *
     * Returns an array with RGB values of the Identicon's color. Colors may be NULL if no image has been generated
     * so far (e.g., when calling the method on a new Identicon()).
	 *
	 * @return array
	 */
	public function getColor()
    {
		$colors = $this->generator->getColor();

        return [
            "r" => $colors[0],
            "g" => $colors[1],
            "b" => $colors[2]
        ];
	}
}
