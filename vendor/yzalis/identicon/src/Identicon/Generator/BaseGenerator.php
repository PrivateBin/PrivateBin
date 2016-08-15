<?php

namespace Identicon\Generator;

use Identicon\Generator\GeneratorInterface;

/**
 * @author Benjamin Laugueux <benjamin@yzalis.com>
 */
class BaseGenerator
{
    /**
     * @var mixed
     */
    protected $generatedImage;

    /**
     * @var integer
     */
    protected $color;

    /**
     * @var integer
     */
    protected $backgroundColor;

    /**
     * @var integer
     */
    protected $size;

    /**
     * @var integer
     */
    protected $pixelRatio;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var array
     */
    private $arrayOfSquare = array();

    /**
     * Set the image color
     *
     * @param string|array $color The color in hexa (6 chars) or rgb array
     *
     * @return this
     */
    public function setColor($color)
    {
        if (null === $color) {
            return $this;
        }

        $this->color = $this->convertColor($color);

        return $this;
    }

    /**
     * Set the image background color
     *
     * @param string|array $backgroundColor The color in hexa (6 chars) or rgb array
     *
     * @return this
     */
    public function setBackgroundColor($backgroundColor)
    {
        if (null === $backgroundColor) {
            return $this;
        }

        $this->backgroundColor = $this->convertColor($backgroundColor);

        return $this;
    }

    private function convertColor($color)
    {
        $convertedColor = array();
        if (is_array($color)) {
            $convertedColor[0] = $color[0];
            $convertedColor[1] = $color[1];
            $convertedColor[2] = $color[2];
        } else {
            if (false !== strpos($color, '#')) {
                $color = substr($color, 1);
            }
            $convertedColor[0] = hexdec(substr($color, 0, 2));
            $convertedColor[1] = hexdec(substr($color, 2, 2));
            $convertedColor[2] = hexdec(substr($color, 4, 2));
        }

        return $convertedColor;
    }

    /**
     * Get the color
     *
     * @return array
     */
    public function getColor()
    {
        return $this->color;
    }


    /**
     * Get the background color
     *
     * @return array
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Convert the hash into an multidimensionnal array of boolean
     *
     * @return this
     */
    private function convertHashToArrayOfBoolean()
    {
        preg_match_all('/(\w)(\w)/', $this->hash, $chars);
        foreach ($chars[1] as $i => $char) {
            if ($i % 3 == 0) {
                $this->arrayOfSquare[$i/3][0] = $this->convertHexaToBoolean($char);
                $this->arrayOfSquare[$i/3][4] = $this->convertHexaToBoolean($char);
            } elseif ($i % 3 == 1) {
                $this->arrayOfSquare[$i/3][1] = $this->convertHexaToBoolean($char);
                $this->arrayOfSquare[$i/3][3] = $this->convertHexaToBoolean($char);
            } else {
                $this->arrayOfSquare[$i/3][2] = $this->convertHexaToBoolean($char);
            }
            ksort($this->arrayOfSquare[$i/3]);
        }

        $this->color[0] = hexdec(array_pop($chars[1]))*16;
        $this->color[1] = hexdec(array_pop($chars[1]))*16;
        $this->color[2] = hexdec(array_pop($chars[1]))*16;

        return $this;
    }

    /**
     * Convert an heaxecimal number into a boolean
     *
     * @param string $hexa
     *
     * @return boolean
     */
    private function convertHexaToBoolean($hexa)
    {
        return (bool) intval(round(hexdec($hexa)/10));
    }

    /**
     *
     *
     * @return array
     */
    public function getArrayOfSquare()
    {
        return $this->arrayOfSquare;
    }

    /**
     * Get the identicon string hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Generate a hash fron the original string
     *
     * @param string $string
     *
     * @return this
     */
    public function setString($string)
    {
        if (null === $string) {
            throw new \Exception('The string cannot be null.');
        }

        $this->hash = md5($string);

        $this->convertHashToArrayOfBoolean();

        return $this;
    }

    /**
     * Set the image size
     *
     * @param integer $size
     *
     * @return this
     */
    public function setSize($size)
    {
        if (null === $size) {
            return $this;
        }

        $this->size = $size;
        $this->pixelRatio = round($size / 5);

        return $this;
    }

    /**
     * Get the image size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the pixel ratio
     *
     * @return array
     */
    public function getPixelRatio()
    {
        return $this->pixelRatio;
    }
}
