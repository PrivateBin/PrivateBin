<?php

namespace Identicon\Generator;

use Identicon\Generator\GeneratorInterface;

/**
 * @author Benjamin Laugueux <benjamin@yzalis.com>
 */
class GdGenerator extends BaseGenerator implements GeneratorInterface
{
    public function __construct()
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD does not appear to be avaliable in your PHP installation. Please try another generator');
        }
    }

    private function generateImage()
    {
        // prepare image
        $this->generatedImage = imagecreatetruecolor($this->getPixelRatio() * 5, $this->getPixelRatio() * 5);

        $rgbBackgroundColor = $this->getBackgroundColor();
        if (null === $rgbBackgroundColor) {
            $background = imagecolorallocate($this->generatedImage, 0, 0, 0);
            imagecolortransparent($this->generatedImage, $background);
        } else {
            $background = imagecolorallocate($this->generatedImage, $rgbBackgroundColor[0], $rgbBackgroundColor[1], $rgbBackgroundColor[2]);
            imagefill($this->generatedImage, 0, 0, $background);
        }

        // prepage color
        $rgbColor = $this->getColor();
        $gdColor = imagecolorallocate($this->generatedImage, $rgbColor[0], $rgbColor[1], $rgbColor[2]);

        // draw content
        foreach ($this->getArrayOfSquare() as $lineKey => $lineValue) {
            foreach ($lineValue as $colKey => $colValue) {
                if (true === $colValue) {
                    imagefilledrectangle($this->generatedImage, $colKey * $this->getPixelRatio(), $lineKey * $this->getPixelRatio(), ($colKey + 1) * $this->getPixelRatio(), ($lineKey + 1) * $this->getPixelRatio(), $gdColor);
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getImageBinaryData($string, $size = null, $color = null, $backgroundColor = null)
    {
        ob_start();
        imagepng($this->getImageResource($string, $size, $color, $backgroundColor));
        $imageData = ob_get_contents();
        ob_end_clean();

        return $imageData;
    }

    /**
     * {@inheritDoc}
     */
    public function getImageResource($string, $size = null, $color = null, $backgroundColor = null)
    {
        $this
            ->setString($string)
            ->setSize($size)
            ->setColor($color)
            ->setBackgroundColor($backgroundColor)
            ->generateImage();

        return $this->generatedImage;
    }
}
