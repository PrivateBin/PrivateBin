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

use Jdenticon\Color;

/**
 * Specifies the color style of an identicon.
 */
class IdenticonStyle
{
    private Color $backgroundColor;
    private float $padding;
    private float $colorSaturation;
    private float $grayscaleSaturation;
    private array $colorLightness;
    private array $grayscaleLightness;
    private ?array $hues = null;

    public function __construct(?array $options = null)
    {
        $this->backgroundColor = self::getDefaultBackgroundColor();
        $this->padding = self::getDefaultPadding();
        $this->colorSaturation = self::getDefaultColorSaturation();
        $this->grayscaleSaturation = self::getDefaultGrayscaleSaturation();
        $this->colorLightness = self::getDefaultColorLightness();
        $this->grayscaleLightness = self::getDefaultGrayscaleLightness();

        if ($options !== null) {
            $this->setOptions($options);
        }
    }

    /**
     * Gets an associative array of all options of this style.
     *
     * @return array
     */
    public function getOptions(): array
    {
        $options = [];

        $options['backgroundColor'] = $this->getBackgroundColor()->__toString();
        $options['padding'] = $this->getPadding();
        $options['colorSaturation'] = $this->getColorSaturation();
        $options['grayscaleSaturation'] = $this->getGrayscaleSaturation();
        $options['colorLightness'] = $this->getColorLightness();
        $options['grayscaleLightness'] = $this->getGrayscaleLightness();

        if ($this->hues !== null) {
            $options['hues'] = $this->getHues();
        }

        return $options;
    }

    /**
     * Sets options in this style by specifying an associative array of option
     * values.
     *
     * @param array $options Options to set.
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
            case 'backgroundcolor':
                return $this->getBackgroundColor();
            case 'padding':
                return $this->getPadding();
            case 'colorsaturation':
                return $this->getColorSaturation();
            case 'grayscalesaturation':
                return $this->getGrayscaleSaturation();
            case 'colorlightness':
                return $this->getColorLightness();
            case 'grayscalelightness':
                return $this->getGrayscaleLightness();
            case 'hues':
                return $this->getHues();
            default:
                throw new \InvalidArgumentException(
                    "Unknown IdenticonStyle option '$name'.");
        }
    }

    public function __set(string $name, $value): void
    {
        switch (strtolower($name)) {
            case 'backgroundcolor':
                $this->setBackgroundColor($value);
                break;
            case 'padding':
                $this->setPadding($value);
                break;
            case 'colorsaturation':
                $this->setColorSaturation($value);
                break;
            case 'grayscalesaturation':
                $this->setGrayscaleSaturation($value);
                break;
            case 'colorlightness':
                $this->setColorLightness($value);
                break;
            case 'grayscalelightness':
                $this->setGrayscaleLightness($value);
                break;
            case 'hues':
                $this->setHues($value);
                break;
            default:
                throw new \InvalidArgumentException(
                    "Unknown IdenticonStyle option '$name'.");
        }
    }

    /**
     * Normalizes a hue to the first turn [0, 360).
     *
     * @param int|float $hue
     * @return integer
     */
    private static function normalizeHue($hue): int
    {
        if (!is_numeric($hue)) {
            throw new \InvalidArgumentException(
                "'$hue' is not a valid hue.");
        }

        $hue = (int)$hue % 360;
        if ($hue < 0) {
            $hue += 360;
        }

        return $hue;
    }

    /**
     * Gets an array of allowed hues, or null if there are no restrictions.
     *
     * @return array<int>|null
     */
    public function getHues(): ?array
    {
        return $this->hues;
    }

    /**
     * Sets the allowed hues of generated icons.
     *
     * @param array<integer>|integer|null $value A hue specified in degrees,
     *      or an array of hues specified in degrees. If set to null, the hue
     *      list is cleared.
     * @return self
     */
    public function setHues($value): self
    {
        $hues = [];

        if ($value !== null) {
            if (is_array($value)) {
                foreach ($value as $hue) {
                    $hues[] = self::normalizeHue($hue);
                }
            } else {
                $hues[] = self::normalizeHue($value);
            }
        }

        $this->hues = empty($hues) ? null : $hues;
        return $this;
    }

    /**
     * Gets the padding of an icon in percents in the range [0.0, 0.4].
     *
     * @return float
     */
    public function getPadding(): float
    {
        return $this->padding;
    }

    /**
     * Sets the padding of an icon in percents.
     *
     * @param float $value New padding in the range [0.0, 0.4].
     * @return self
     */
    public function setPadding(float $value): self
    {
        if ($value < 0 || $value > 0.4) {
            throw new \InvalidArgumentException(
                "Padding '$value' out of range. ".
                "Values in the range [0.0, 0.4] are allowed.");
        }
        $this->padding = (float)$value;
        return $this;
    }

    /**
     * Gets the color of the identicon background.
     *
     * @return \Jdenticon\Color
     */
    public function getBackgroundColor(): Color
    {
        return $this->backgroundColor;
    }

    /**
     * Sets the color of the identicon background.
     *
     * @param \Jdenticon\Color|string $value New background color.
     * @return \Jdenticon\IdenticonStyle
     */
    public function setBackgroundColor($value): self
    {
        if ($value instanceof Color) {
            $this->backgroundColor = $value;
        } else {
            $this->backgroundColor = Color::parse($value);
        }

        return $this;
    }

    /**
     * Gets the saturation of the originally grayscale identicon shapes.
     *
     * @return float  Saturation in the range [0.0, 1.0].
     */
    public function getGrayscaleSaturation(): float
    {
        return $this->grayscaleSaturation;
    }

    /**
     * Sets the saturation of the originally grayscale identicon shapes.
     *
     * @param $value float  Saturation in the range [0.0, 1.0].
     * @return self
     */
    public function setGrayscaleSaturation(float $value): self
    {
        if ($value < 0 || $value > 1) {
            throw new \InvalidArgumentException(
                "The grayscale saturation was invalid. ".
                "Only values in the range [0.0, 1.0] are allowed.");
        }
        $this->grayscaleSaturation = (float)$value;
        return $this;
    }

    /**
     * Gets the saturation of the colored identicon shapes.
     *
     * @return float Saturation in the range [0.0, 1.0].
     */
    public function getColorSaturation(): float
    {
        return $this->colorSaturation;
    }

    /**
     * Sets the saturation of the colored identicon shapes.
     *
     * @param $value float  Saturation in the range [0.0, 1.0].
     * @return self
     */
    public function setColorSaturation(float $value): self
    {
        if ($value < 0 || $value > 1) {
            throw new \InvalidArgumentException(
                "The color saturation was invalid. ".
                "Only values in the range [0.0, 1.0] are allowed.");
        }
        $this->colorSaturation = (float)$value;
        return $this;
    }

    /**
     * Gets the value of the ColorLightness property.
     *
     * @return array<float>
     */
    public function getColorLightness(): array
    {
        return $this->colorLightness;
    }

    /**
     * Sets the value of the ColorLightness property.
     *
     * @param array<float> $value Lightness range.
     * @return self
     */
    public function setColorLightness(array $value): self
    {
        if (!array_key_exists(0, $value) ||
            !array_key_exists(1, $value) ||
            !is_numeric($value[0]) ||
            !is_numeric($value[1]) ||
            $value[0] < 0 || $value[0] > 1 ||
            $value[1] < 0 || $value[1] > 1
        ) {
            throw new \InvalidArgumentException(
                "The value passed to setColorLightness was invalid. ".
                "Please check the documentation.");
        }

        $this->colorLightness = [(float)$value[0], (float)$value[1]];
        return $this;
    }

    /**
     * Gets the value of the GrayscaleLightness property.
     *
     * @return array<float>
     */
    public function getGrayscaleLightness(): array
    {
        return $this->grayscaleLightness;
    }

    /**
     * Sets the value of the GrayscaleLightness property.
     *
     * @param array<float> $value Lightness range.
     * @return self
     */
    public function setGrayscaleLightness(array $value): self
    {
        if (!array_key_exists(0, $value) ||
            !array_key_exists(1, $value) ||
            !is_numeric($value[0]) ||
            !is_numeric($value[1]) ||
            $value[0] < 0 || $value[0] > 1 ||
            $value[1] < 0 || $value[1] > 1
        ) {
            throw new \InvalidArgumentException(
                "The value passed to setGrayscaleLightness was invalid. ".
                "Please check the documentation.");
        }
        $this->grayscaleLightness = [(float)$value[0], (float)$value[1]];
        return $this;
    }



    /**
     * Gets the default value of the BackgroundColor property. Resolves to transparent.
     *
     * @return \Jdenticon\Color
     */
    public static function getDefaultBackgroundColor(): Color
    {
        return Color::fromRgb(255, 255, 255, 255);
    }

    /**
     * Gets the default value of the Padding property. Resolves to 0.08.
     *
     * @return float
     */
    public static function getDefaultPadding(): float
    {
        return 0.08;
    }

    /**
     * Gets the default value of the ColorSaturation property. Resolves to 0.5.
     *
     * @return float
     */
    public static function getDefaultColorSaturation(): float
    {
        return 0.5;
    }

    /**
     * Gets the default value of the GrayscaleSaturation property. Resolves to 0.
     *
     * @return float
     */
    public static function getDefaultGrayscaleSaturation(): float
    {
        return 0;
    }

    /**
     * Gets the default value of the ColorLightness property. Resolves to [0.4, 0.8].
     *
     * @return array<float>
     */
    public static function getDefaultColorLightness(): array
    {
        return [0.4, 0.8];
    }

    /**
     * Gets the default value of the GrayscaleLightness property. Resolves to [0.3, 0.9].
     *
     * @return array<float>
     */
    public static function getDefaultGrayscaleLightness(): array
    {
        return [0.3, 0.9];
    }
}
