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

namespace Jdenticon;

/**
 * Represents a 24-bit color with a 8-bit alpha channel.
 */
class Color
{
    private static $lightnessCompensations = array(
        0.55, 0.5, 0.5, 0.46, 0.6, 0.55, 0.55);
    
    /**
     * The red component of the color in the range [0, 255].
     * @var int
     */
    public $r;
    
    /**
     * The green component of the color in the range [0, 255].
     * @var int
     */
    public $g;
    
    /**
     * The blue component of the color in the range [0, 255].
     * @var int
     */
    public $b;
    
    /**
     * The alpha component of the color in the range [0, 255].
     * @var int
     */
    public $a;

    // Users of the struct should use the static factory methods 
    // to create Color value.
    private function __construct()
    {
    }

    /**
     * Creates a Color from an RGB value.
     *
     * @param int $alpha Alpha channel value in the range [0, 255].
     * @param int $red Red component in the range [0, 255].
     * @param int $green GReen component in the range [0, 255].
     * @param int $blue Blue component in the range [0, 255].
     */
    public static function fromRgb($red, $green, $blue, $alpha = 255)
    {
        $color = new Color();
        $color->r = $red;
        $color->g = $green;
        $color->b = $blue;
        $color->a = $alpha;
        return $color;
    }

    /**
     * Creates a Color instance from HSL color parameters.
     *
     * @param float $hue Hue in the range [0, 1]
     * @param float $saturation Saturation in the range [0, 1]
     * @param float $lightness Lightness in the range [0, 1]
     * @param float $alpha Alpha channel value in the range [0, 1].
     */
    public static function fromHsl($hue, $saturation, $lightness, $alpha = 1.0)
    {
        if ($hue < 0) $hue = 0;
        if ($hue > 1) $hue = 1;
        
        if ($saturation < 0) $saturation = 0;
        if ($saturation > 1) $saturation = 1;
        
        if ($lightness < 0) $lightness = 0;
        if ($lightness > 1) $lightness = 1;
        
        if ($alpha < 0) $alpha = 0;
        if ($alpha > 1) $alpha = 1;

        // Based on http://www.w3.org/TR/2011/REC-css3-color-20110607/#hsl-color
        if ($saturation == 0) {
            $value = (int)($lightness * 255);
            return self::fromRgb($value, $value, $value, (int)($alpha * 255));
        } else {
            if ($lightness <= 0.5) {
                $m2 = $lightness * ($saturation + 1);
            } else {
                $m2 = $lightness + $saturation - $lightness * $saturation;
            }
            
            $m1 = $lightness * 2 - $m2;

            return self::fromRgb(
                self::hueToRgb($m1, $m2, $hue * 6 + 2),
                self::hueToRgb($m1, $m2, $hue * 6),
                self::hueToRgb($m1, $m2, $hue * 6 - 2),
                (int)($alpha * 255));
        }
    }
    
    /**
     * Creates a Color> instance from HSL color parameters and will compensate 
     * the lightness for hues that appear to be darker than others.
     *
     * @param float $hue Hue in the range [0, 1].
     * @param float $saturation Saturation in the range [0, 1].
     * @param float $lightness Lightness in the range [0, 1].
     * @param float $alpha Alpha channel value in the range [0, 1].
     */
    public static function fromHslCompensated($hue, $saturation, $lightness, $alpha = 1.0)
    {
        if ($hue < 0) $hue = 0;
        if ($hue > 1) $hue = 1;
        
        $lightnessCompensation = self::$lightnessCompensations[(int)($hue * 6 + 0.5)];
        
        // Adjust the input lightness relative to the compensation
        $lightness = $lightness < 0.5 ?
            $lightness * $lightnessCompensation * 2 : 
            $lightnessCompensation + ($lightness - 0.5) * (1 - $lightnessCompensation) * 2;

        return self::fromHsl($hue, $saturation, $lightness, $alpha);
    }

    // Helper method for FromHsl
    private static function hueToRgb($m1, $m2, $h)
    {
        if ($h < 0) {
            $h = $h + 6;
        } elseif ($h > 6) {
            $h = $h - 6;
        }
        
        if ($h < 1) {
            $r = $m1 + ($m2 - $m1) * $h;
        } elseif ($h < 3) {
            $r = $m2;
        } elseif ($h < 4) {
            $r = $m1 + ($m2 - $m1) * (4 - $h);
        } else {
            $r = $m1;
        }
        
        return (int)(255 * $r);
    }

    /**
     * Gets the argb value of this color.
     *
     * @return int
     */
    public function toRgba()
    {
        return 
            ($this->r << 24) |
            ($this->g << 16) |
            ($this->b << 8) |
            ($this->a);
    }

    /**
     * Gets a hexadecimal representation of this color on the format #rrggbbaa.
     *
     * @return string
     */
    public function __toString()
    {
        return '#' . bin2hex(pack('N', $this->toRgba()));
    }
    
    /**
     * Gets a hexadecimal representation of this color on the format #rrggbbaa.
     *
     * @return string
     */
    public function toHexString($length = 8)
    {
        if ($length === 8) {
            return $this->__toString();
        }
        return '#' . substr(bin2hex(pack('N', $this->toRgba())), 0, 6);
    }
    
    /**
     * Tries to parse a value as a Color.
     * 
     * @param mixed $value Value to parse.
     * @throws InvalidArgumentException
     * @return \Jdenticon\Color
     */
    public static function parse($value) {
        if ($value instanceof Color) {
            return $value;
        }
        
        $value = strtolower("$value");
        
        if (preg_match('/^#?[0-9a-f]{3,8}$/', $value) &&
            self::parseHexColor($value, $result)
        ) {
            return $result;
        }
        
        if (preg_match(
                '/^rgba?\\(([^,]+),([^,]+),([^,]+)(?:,([^,]+))?\\)$/', 
                $value, $matches) &&
            self::parseRgbComponent($matches[1], $r) &&
            self::parseRgbComponent($matches[2], $g) &&
            self::parseRgbComponent($matches[3], $b) &&
            self::parseAlpha(isset($matches[4]) ? $matches[4] : null, $a)
        ) {
            return self::fromRgb($r, $g, $b, (int)(255 * $a));
        }
        
        if (preg_match(
                '/^hsla?\\(([^,]+),([^,]+),([^,]+)(?:,([^,]+))?\\)$/', 
                $value, $matches) &&
            self::parseHue($matches[1], $h) &&
            self::parsePercent($matches[2], $s) &&
            self::parsePercent($matches[3], $l) &&
            self::parseAlpha(isset($matches[4]) ? $matches[4] : null, $a)
        ) {
            return self::fromHsl($h, $s, $l, $a);
        }
        
        $result = self::parseNamedColor($value);
        if ($result !== null) {
            return $result;
        }
        
        throw new \InvalidArgumentException(
            "Cannot parse '$value' as a color.");
    }
    
    /**
     * Parses a percent value.
     * 
     * @param string $input Input string.
     * @param float $result Resulting value in range [0, 1].
     * 
     * @return boolean
     */
    private static function parsePercent($input, &$result)
    {
        // Detect and remove percent sign
        if (preg_match('/^\\s*(\\d*(?:\\.\\d*)?)%\\s*$/', $input, $matches)) {
            $result = floatval($matches[1]) / 100;
            
            if ($result < 0) $result = 0;
            if ($result > 1) $result = 1;
            
            return true;
        }
        return false;
    }

    /**
     * Parses an alpha value.
     * 
     * @param string $input Input string.
     * @param float $result Resulting alpha in range [0, 1].
     * 
     * @return boolean
     */
    private static function parseAlpha($input, &$result)
    {
        if ($input === null ||
            $input === ''
        ) {
            $result = 1;
            return true;
        }
        
        if (preg_match('/^\\s*(\\d*(?:\\.\\d*)?)(%?)\\s*$/', $input, $matches)) {
            $result = floatval($matches[1]);
            
            // Percentage
            if ($matches[2] !== '') {
                $result = $result / 100;
            }
            
            if ($result < 0) $result = 0;
            if ($result > 1) $result = 1;
            
            return true;
        }
        
        return false;
    }

    /**
     * Parses an RGB component.
     * 
     * @param string $input  Input string.
     * @param float  $result Hue in range [0, 255].
     * 
     * @return boolean
     */
    private static function parseRgbComponent($input, &$result)
    {
        if (preg_match('/^\\s*(\\d*(?:\\.\\d*)?)(%?)\\s*$/', $input, $matches)) {
            $result = floatval($matches[1]);
            
            if ($matches[2] === '%') {
                $result = 255 * $result / 100;
            }
            
            $result = (int)$result;
            
            if ($result < 0) $result = 0;
            if ($result > 255) $result = 255;
            
            return true;
        }
        return false;
    }

    /**
     * Parses a hue component.
     * 
     * @param string $input Input string.
     * @param float $result Hue in range [0, 1].
     * 
     * @return boolean
     */
    private static function parseHue($input, &$result)
    {
        if (preg_match(
            '/^\s*(\d*(?:\.\d*)?)(deg|grad|rad|turn|)\s*$/', 
            $input, $matches)
        ) {
            $result = floatval($matches[1]);
            
            // Percentage
            switch ($matches[2]) {
                case "grad":
                    // Gradians: range 0 - 400
                    $result = $result / 400;
                    break;
                case "rad":
                    // Radians: range 0 - 2pi
                    $result = $result / M_PI / 2;
                    break;
                case "turn":
                    // Turns: range 0 - 1
                    $result = $result;
                    break;
                default:
                    // Degree: range 0 - 360
                    $result = $result / 360;
                    break;
            }
            
            $result = fmod($result, 1);
            
            if ($result < 0) {
                $result += 1;
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * Parses a hex color string.
     * 
     * @param string $input Input string.
     * @param float $result Hue in range [0, 1].
     * 
     * @return boolean
     */
    private static function parseHexColor($input, &$result) 
    {
        if ($input[0] === '#') {
            $input = substr($input, 1);
        }

        // intval does not support unsigned 32-bit integers
        // so we need to parse large numbers stepwise
        $numeric24bit = intval(substr($input, 0, 6), 16);
        $alpha8bit = intval(substr($input, 6, 2), 16);

        switch (strlen($input)) {
            case 3:
                $result = self::fromRgb(
                    (($numeric24bit & 0xf00) >> 8) |
                    (($numeric24bit & 0xf00) >> 4),
                    (($numeric24bit & 0x0f0) >> 4) |
                    (($numeric24bit & 0x0f0)),
                    (($numeric24bit & 0x00f) << 4) |
                    (($numeric24bit & 0x00f))
                    );
                return true;
                
            case 4:
                $result = self::fromRgb(
                    (($numeric24bit & 0xf000) >> 12) |
                    (($numeric24bit & 0xf000) >> 8),
                    (($numeric24bit & 0x0f00) >> 8) |
                    (($numeric24bit & 0x0f00) >> 4),
                    (($numeric24bit & 0x00f0) >> 4) |
                    (($numeric24bit & 0x00f0)),
                    (($numeric24bit & 0x000f) << 4) |
                    (($numeric24bit & 0x000f))
                    );
                return true;
                
            case 6:
                $result = self::fromRgb(
                    0xff & ($numeric24bit >> 16),
                    0xff & ($numeric24bit >> 8),
                    0xff & ($numeric24bit)
                    );
                return true;
                
            case 8:
                $result = self::fromRgb(
                    0xff & ($numeric24bit >> 16),
                    0xff & ($numeric24bit >> 8),
                    0xff & ($numeric24bit),
                    0xff & ($alpha8bit)
                    );
                return true;
        }
        
        return false;
    }
    
    /**
     * Looks up a named color to a Color instance.
     * 
     * @param string $input Input string.
     * 
     * @return \Jdenticon\Color
     */
    private static function parseNamedColor($input) 
    {
        // Source: https://www.w3.org/TR/css-color-4/#named-colors
        switch ($input) {
            case 'aliceblue': return self::fromRgb(240,248,255);
            case 'antiquewhite': return self::fromRgb(250,235,215);
            case 'aqua': return self::fromRgb(0,255,255);
            case 'aquamarine': return self::fromRgb(127,255,212);
            case 'azure': return self::fromRgb(240,255,255);
            case 'beige': return self::fromRgb(245,245,220);
            case 'bisque': return self::fromRgb(255,228,196);
            case 'black': return self::fromRgb(0,0,0);
            case 'blanchedalmond': return self::fromRgb(255,235,205);
            case 'blue': return self::fromRgb(0,0,255);
            case 'blueviolet': return self::fromRgb(138,43,226);
            case 'brown': return self::fromRgb(165,42,42);
            case 'burlywood': return self::fromRgb(222,184,135);
            case 'cadetblue': return self::fromRgb(95,158,160);
            case 'chartreuse': return self::fromRgb(127,255,0);
            case 'chocolate': return self::fromRgb(210,105,30);
            case 'coral': return self::fromRgb(255,127,80);
            case 'cornflowerblue': return self::fromRgb(100,149,237);
            case 'cornsilk': return self::fromRgb(255,248,220);
            case 'crimson': return self::fromRgb(220,20,60);
            case 'cyan': return self::fromRgb(0,255,255);
            case 'darkblue': return self::fromRgb(0,0,139);
            case 'darkcyan': return self::fromRgb(0,139,139);
            case 'darkgoldenrod': return self::fromRgb(184,134,11);
            case 'darkgray': return self::fromRgb(169,169,169);
            case 'darkgreen': return self::fromRgb(0,100,0);
            case 'darkgrey': return self::fromRgb(169,169,169);
            case 'darkkhaki': return self::fromRgb(189,183,107);
            case 'darkmagenta': return self::fromRgb(139,0,139);
            case 'darkolivegreen': return self::fromRgb(85,107,47);
            case 'darkorange': return self::fromRgb(255,140,0);
            case 'darkorchid': return self::fromRgb(153,50,204);
            case 'darkred': return self::fromRgb(139,0,0);
            case 'darksalmon': return self::fromRgb(233,150,122);
            case 'darkseagreen': return self::fromRgb(143,188,143);
            case 'darkslateblue': return self::fromRgb(72,61,139);
            case 'darkslategray': return self::fromRgb(47,79,79);
            case 'darkslategrey': return self::fromRgb(47,79,79);
            case 'darkturquoise': return self::fromRgb(0,206,209);
            case 'darkviolet': return self::fromRgb(148,0,211);
            case 'deeppink': return self::fromRgb(255,20,147);
            case 'deepskyblue': return self::fromRgb(0,191,255);
            case 'dimgray': return self::fromRgb(105,105,105);
            case 'dimgrey': return self::fromRgb(105,105,105);
            case 'dodgerblue': return self::fromRgb(30,144,255);
            case 'firebrick': return self::fromRgb(178,34,34);
            case 'floralwhite': return self::fromRgb(255,250,240);
            case 'forestgreen': return self::fromRgb(34,139,34);
            case 'fuchsia': return self::fromRgb(255,0,255);
            case 'gainsboro': return self::fromRgb(220,220,220);
            case 'ghostwhite': return self::fromRgb(248,248,255);
            case 'gold': return self::fromRgb(255,215,0);
            case 'goldenrod': return self::fromRgb(218,165,32);
            case 'gray': return self::fromRgb(128,128,128);
            case 'green': return self::fromRgb(0,128,0);
            case 'greenyellow': return self::fromRgb(173,255,47);
            case 'grey': return self::fromRgb(128,128,128);
            case 'honeydew': return self::fromRgb(240,255,240);
            case 'hotpink': return self::fromRgb(255,105,180);
            case 'indianred': return self::fromRgb(205,92,92);
            case 'indigo': return self::fromRgb(75,0,130);
            case 'ivory': return self::fromRgb(255,255,240);
            case 'khaki': return self::fromRgb(240,230,140);
            case 'lavender': return self::fromRgb(230,230,250);
            case 'lavenderblush': return self::fromRgb(255,240,245);
            case 'lawngreen': return self::fromRgb(124,252,0);
            case 'lemonchiffon': return self::fromRgb(255,250,205);
            case 'lightblue': return self::fromRgb(173,216,230);
            case 'lightcoral': return self::fromRgb(240,128,128);
            case 'lightcyan': return self::fromRgb(224,255,255);
            case 'lightgoldenrodyellow': return self::fromRgb(250,250,210);
            case 'lightgray': return self::fromRgb(211,211,211);
            case 'lightgreen': return self::fromRgb(144,238,144);
            case 'lightgrey': return self::fromRgb(211,211,211);
            case 'lightpink': return self::fromRgb(255,182,193);
            case 'lightsalmon': return self::fromRgb(255,160,122);
            case 'lightseagreen': return self::fromRgb(32,178,170);
            case 'lightskyblue': return self::fromRgb(135,206,250);
            case 'lightslategray': return self::fromRgb(119,136,153);
            case 'lightslategrey': return self::fromRgb(119,136,153);
            case 'lightsteelblue': return self::fromRgb(176,196,222);
            case 'lightyellow': return self::fromRgb(255,255,224);
            case 'lime': return self::fromRgb(0,255,0);
            case 'limegreen': return self::fromRgb(50,205,50);
            case 'linen': return self::fromRgb(250,240,230);
            case 'magenta': return self::fromRgb(255,0,255);
            case 'maroon': return self::fromRgb(128,0,0);
            case 'mediumaquamarine': return self::fromRgb(102,205,170);
            case 'mediumblue': return self::fromRgb(0,0,205);
            case 'mediumorchid': return self::fromRgb(186,85,211);
            case 'mediumpurple': return self::fromRgb(147,112,219);
            case 'mediumseagreen': return self::fromRgb(60,179,113);
            case 'mediumslateblue': return self::fromRgb(123,104,238);
            case 'mediumspringgreen': return self::fromRgb(0,250,154);
            case 'mediumturquoise': return self::fromRgb(72,209,204);
            case 'mediumvioletred': return self::fromRgb(199,21,133);
            case 'midnightblue': return self::fromRgb(25,25,112);
            case 'mintcream': return self::fromRgb(245,255,250);
            case 'mistyrose': return self::fromRgb(255,228,225);
            case 'moccasin': return self::fromRgb(255,228,181);
            case 'navajowhite': return self::fromRgb(255,222,173);
            case 'navy': return self::fromRgb(0,0,128);
            case 'oldlace': return self::fromRgb(253,245,230);
            case 'olive': return self::fromRgb(128,128,0);
            case 'olivedrab': return self::fromRgb(107,142,35);
            case 'orange': return self::fromRgb(255,165,0);
            case 'orangered': return self::fromRgb(255,69,0);
            case 'orchid': return self::fromRgb(218,112,214);
            case 'palegoldenrod': return self::fromRgb(238,232,170);
            case 'palegreen': return self::fromRgb(152,251,152);
            case 'paleturquoise': return self::fromRgb(175,238,238);
            case 'palevioletred': return self::fromRgb(219,112,147);
            case 'papayawhip': return self::fromRgb(255,239,213);
            case 'peachpuff': return self::fromRgb(255,218,185);
            case 'peru': return self::fromRgb(205,133,63);
            case 'pink': return self::fromRgb(255,192,203);
            case 'plum': return self::fromRgb(221,160,221);
            case 'powderblue': return self::fromRgb(176,224,230);
            case 'purple': return self::fromRgb(128,0,128);
            case 'rebeccapurple': return self::fromRgb(102,51,153);
            case 'red': return self::fromRgb(255,0,0);
            case 'rosybrown': return self::fromRgb(188,143,143);
            case 'royalblue': return self::fromRgb(65,105,225);
            case 'saddlebrown': return self::fromRgb(139,69,19);
            case 'salmon': return self::fromRgb(250,128,114);
            case 'sandybrown': return self::fromRgb(244,164,96);
            case 'seagreen': return self::fromRgb(46,139,87);
            case 'seashell': return self::fromRgb(255,245,238);
            case 'sienna': return self::fromRgb(160,82,45);
            case 'silver': return self::fromRgb(192,192,192);
            case 'skyblue': return self::fromRgb(135,206,235);
            case 'slateblue': return self::fromRgb(106,90,205);
            case 'slategray': return self::fromRgb(112,128,144);
            case 'slategrey': return self::fromRgb(112,128,144);
            case 'snow': return self::fromRgb(255,250,250);
            case 'springgreen': return self::fromRgb(0,255,127);
            case 'steelblue': return self::fromRgb(70,130,180);
            case 'tan': return self::fromRgb(210,180,140);
            case 'teal': return self::fromRgb(0,128,128);
            case 'thistle': return self::fromRgb(216,191,216);
            case 'tomato': return self::fromRgb(255,99,71);
            case 'transparent': return self::fromRgb(0,0,0,0);
            case 'turquoise': return self::fromRgb(64,224,208);
            case 'violet': return self::fromRgb(238,130,238);
            case 'wheat': return self::fromRgb(245,222,179);
            case 'white': return self::fromRgb(255,255,255);
            case 'whitesmoke': return self::fromRgb(245,245,245);
            case 'yellow': return self::fromRgb(255,255,0);
            case 'yellowgreen': return self::fromRgb(154,205,50);
            default: return null;
        }
    }
}
