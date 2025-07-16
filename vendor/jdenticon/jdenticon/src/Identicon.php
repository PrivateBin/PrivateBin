<?php
/**
 * This file is part of Jdenticon for PHP.
 * https://github.com/dmester/jdenticon-php/
 *
 * Copyright (c) 2025 Daniel Mester Pirttijärvi
 * Copyright (c) 2024 Peter Putzer
 *
 * For full license information, please see the LICENSE file that was
 * distributed with this source code.
 */

namespace Jdenticon;

use Jdenticon\IdenticonStyle;
use Jdenticon\Rendering\Rectangle;
use Jdenticon\Rendering\RendererInterface;
use Jdenticon\Rendering\IconGenerator;
use Jdenticon\Rendering\InternalPngRenderer;
use Jdenticon\Rendering\ImagickRenderer;
use Jdenticon\Rendering\SvgRenderer;

/**
 * Represents an identicon and its style. This is the entry class to Jdenticon.
 */
class Identicon
{
    /**
     * @var mixed
     */
    private $value;

    private bool $valueSet = false;

    /**
     * Defaults to hash of an empty string.
     */
    private string $hash = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';

    private int $size = 100;
    private IconGenerator $iconGenerator;
    private ?IdenticonStyle $style = null;
    private ?bool $enableImageMagick = null;

    /**
     * Creates an Identicon instance with the specified hash.
     *
     * @param string $hash A binary string containing the hash that will be used
     *      as base for this icon. The hash must contain at least 6 bytes.
     * @param int|float|double $size The size of the icon in pixels (the icon
     *      is quadratic).
     */
    public function __construct(?array $options = null)
    {
        $this->iconGenerator = IconGenerator::getDefaultGenerator();

        if ($options !== null) {
            $this->setOptions($options);
        }

        if ($this->style === null) {
            $this->style = new IdenticonStyle();
        }
    }

    /**
     * Creates an Identicon instance from a specified hash.
     *
     * @param string $hash A binary string containing the hash that will be used
     *      as base for this icon. The hash must contain at least 6 bytes.
     * @param int $size The size of the icon in pixels (the icon is quadratic).
     * @return \Jdenticon\Identicon
     */
    public static function fromHash(string $hash, int $size): self
    {
        return new Identicon(['hash' => $hash, 'size' => $size]);
    }

    /**
     * Creates an Identicon instance from a specified value.
     *
     * @param mixed $value The value that will be used as base for this icon.
     *      The value will be converted to a UTF8 encoded string and then hashed
     *      using SHA1.
     * @param int $size The size of the icon in pixels (the icon is quadratic).
     * @return \Jdenticon\Identicon
     */
    public static function fromValue($value, int $size): self
    {
        return new Identicon(['value' => $value, 'size' => $size]);
    }

    /**
     * Gets an associative array of all options of this identicon.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        $options = [];

        if ($this->valueSet) {
            $options['value'] = $this->getValue();
        } elseif ($this->hash !== null) {
            $options['hash'] = $this->getHash();
        }

        $options['size'] = $this->getSize();
        $options['style'] = $this->getStyle()->getOptions();

        if ($this->enableImageMagick !== null) {
            $options['enableImageMagick'] = $this->getEnableImageMagick();
        }

        if ($this->iconGenerator !== IconGenerator::getDefaultGenerator()) {
            $options['iconGenerator'] = $this->getIconGenerator();
        }

        return $options;
    }

    /**
     * Sets options in this identicon by specifying an associative array of
     * option values.
     *
     * @param array<string, mixed> $options Options to set.
     * @return self
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $this->__set($key, $value);
        }
        return $this;
    }

    public function __get(string $name)
    {
        switch (strtolower($name)) {
            case 'size':
                return $this->getSize();
            case 'hash':
                return $this->getHash();
            case 'value':
                return $this->getValue();
            case 'style':
                return $this->getStyle();
            case 'icongenerator':
                return $this->getIconGenerator();
            case 'enableimagemagick':
                return $this->getEnableImageMagick();
            default:
                throw new \InvalidArgumentException(
                    "Unknown Identicon option '$name'.");
        }
    }

    public function __set(string $name, $value)
    {
        switch (strtolower($name)) {
            case 'size':
                $this->setSize($value);
                break;
            case 'hash':
                $this->setHash($value);
                break;
            case 'value':
                $this->setValue($value);
                break;
            case 'style':
                $this->setStyle($value);
                break;
            case 'icongenerator':
                $this->setIconGenerator($value);
                break;
            case 'enableimagemagick':
                $this->setEnableImageMagick($value);
                break;
            default:
                throw new \InvalidArgumentException(
                    "Unknown Identicon option '$name'.");
        }
    }

    /**
     * Gets the size of the icon in pixels.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Sets the size of this icon in pixels.
     *
     * @param int|float|double $size The width and height of the icon.
     */
    public function setSize($size): void
    {
        if (!is_numeric($size) || $size < 1) {
            throw new \InvalidArgumentException(
                "An invalid identicon size was specified. ".
                "A numeric value >= 1 was expected. Specified value: $size.");
        }

        $this->size = (int)$size;
    }

    /**
     * Gets the size of the icon in pixels.
     */
    public function getEnableImageMagick(): bool
    {
        // Performance of using Imagick on PHP 7 and later is generally worse than using
        // the internal renderer. Because of this, default to false.
        if ($this->enableImageMagick === null) {
            return false;
        }

        return $this->enableImageMagick;
    }

    /**
     * Sets whether ImageMagick should be used to generate PNG icons.
     *
     * @param bool $enable true to enable ImageMagick.
     */
    public function setEnableImageMagick(bool $enable): void
    {
        // Verify that the Imagick extension is installed
        if ($enable && !extension_loaded('imagick')) {
            throw new \Exception(
                'Failed to enable ImageMagick. '.
                'The Imagick PHP extension was not found on this system.');
        }

        $this->enableImageMagick = $enable;
    }

    /**
     * Gets the {@see IconGenerator} used to generate icons.
     *
     * @return \Jdenticon\Rendering\IconGenerator
     */
    public function getIconGenerator(): IconGenerator
    {
        return $this->iconGenerator;
    }

    /**
     * Sets the {@see IconGenerator} used to generate icons.
     *
     * @param \Jdenticon\Rendering\IconGenerator $iconGenerator Icon generator
     *      that will render the shapes of the identicon.
     * @return \Jdenticon\Identicon
     */
    public function setIconGenerator(IconGenerator $iconGenerator): self
    {
        if ($iconGenerator === null) {
            $iconGenerator = IconGenerator::getDefaultGenerator();
        }
        $this->iconGenerator = $iconGenerator;
        return $this;
    }

    /**
     * Gets or sets the style of the icon.
     *
     * @return \Jdenticon\IdenticonStyle
     */
    public function getStyle(): IdenticonStyle
    {
        return $this->style;
    }

    /**
     * Gets or sets the style of the icon.
     *
     * @param array<string, mixed>|\Jdenticon\IdenticonStyle $style The new style of the icon.
     *      NULL will revert the identicon to use the default style.
     * @return self
     */
    public function setStyle($style): self
    {
        if ($style == null) {
            $this->style = new IdenticonStyle();
        } elseif ($style instanceof IdenticonStyle) {
            $this->style = $style;
        } elseif (is_array($style)) {
            $this->style = new IdenticonStyle($style);
        } else {
            throw new \InvalidArgumentException(
                "Invalid indenticon style was specified. ".
                "Allowed values are IdenticonStyle instances and associative ".
                "arrays containing IdenticonStyle options.");
        }

        return $this;
    }

    /**
     * Gets a binary string containing the hash that is used as base for this
     * icon.
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Sets a binary string containing the hash that is used as base for this
     * icon. The string should contain at least 6 bytes.
     *
     * @param string $hash Binary string containing the hash.
     */
    public function setHash(string $hash): self
    {
        if (strlen($hash) < 6) {
            throw new \InvalidArgumentException(
                'An invalid $hash was passed to Identicon. ' .
                'The hash was expected to contain at least 6 bytes.');
        }

        $this->hash = $hash;
        $this->value = null;
        $this->valueSet = false;
        return $this;
    }

    /**
     * Gets a binary string containing the hash that is used as base for this
     * icon.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets a value that will be used as base for this icon. The value will
     * be converted to a string and then hashed using SHA1.
     *
     * @param mixed $value Value that will be hashed.
     */
    public function setValue($value): self
    {
        $this->hash = sha1("$value");
        $this->value = $value;
        $this->valueSet = true;
        return $this;
    }

    /**
     * Gets the bounds of the icon excluding its padding.
     *
     * @return \Jdenticon\Rendering\Rectangle
     */
    public function getIconBounds(): Rectangle
    {
        // Round padding to nearest integer
        $padding = (int)($this->style->getPadding() * $this->size + 0.5);

        return new Rectangle(
            $padding, $padding,
            $this->size - $padding * 2,
            $this->size - $padding * 2);
    }

    private function getRenderer(string $imageFormat): RendererInterface
    {
        switch (strtolower($imageFormat)) {
            case 'svg':
                return new SvgRenderer($this->size, $this->size);

            default:
                return $this->getEnableImageMagick() ?
                    new ImagickRenderer($this->size, $this->size) :
                    new InternalPngRenderer($this->size, $this->size);
        }
    }

    /**
     * Draws this icon using a specified renderer.
     *
     * This method is only intended for usage with custom renderers. A custom
     * renderer could as an example render an Identicon in a file format not
     * natively supported by Jdenticon. To implement a new file format,
     * implement {@see \Jdenticon\Rendering\RendererInterface}.
     *
     * @param \Jdenticon\Rendering\RendererInterface $renderer The renderer used
     *      to render this icon.
     * @param \Jdenticon\Rendering\Rectangle $rect The bounds of the rendered
     *      icon. No padding will be applied to the rectangle. If the parameter
     *      is omitted, the rectangle is calculated from the current icon
     *      size and padding.
     */
    public function draw(RendererInterface $renderer, ?Rectangle $rect = null): void {
        if ($rect === null) {
            $rect = $this->getIconBounds();
        }
        $this->iconGenerator->generate(
            $renderer, $rect, $this->style, $this->hash);
    }

    /**
     * Renders the icon directly to the page output.
     *
     * The method will set the 'Content-Type' HTTP header. You are recommended
     * to set an appropriate 'Cache-Control' header before calling this method
     * to ensure the icon is  cached client side.
     *
     * @param string $imageFormat The image format of the output.
     *      Supported values are 'png' and 'svg'.
     */
    public function displayImage(string $imageFormat = 'png'): void
    {
        $renderer = $this->getRenderer($imageFormat);
        $this->draw($renderer, $this->getIconBounds());
        $mimeType = $renderer->getMimeType();
        $data = $renderer->getData();
        header("Content-Type: $mimeType");
        echo $data;
    }

    /**
     * Renders the icon to a binary string.
     *
     * @param string $imageFormat The image format of the output string.
     *      Supported values are 'png' and 'svg'.
     * @return string
     */
    public function getImageData(string $imageFormat = 'png'): string
    {
        $renderer = $this->getRenderer($imageFormat);
        $this->draw($renderer, $this->getIconBounds());
        return $renderer->getData();
    }

    /**
     * Renders the icon as a data URI. It is recommended to avoid using this
     * method unless really necessary, since it will effectively disable client
     * caching of generated icons, and will also cause the same icon to be
     * rendered multiple times, when used multiple times on a single page.
     *
     * @param string $imageFormat The image format of the data URI.
     *      Supported values are 'png' and 'svg'.
     * @return string
     */
    public function getImageDataUri(string $imageFormat = 'png'): string
    {
        $renderer = $this->getRenderer($imageFormat);
        $this->draw($renderer, $this->getIconBounds());
        $mimeType = $renderer->getMimeType();
        $base64 = base64_encode($renderer->getData());
        return "data:$mimeType;base64,$base64";
    }
}
