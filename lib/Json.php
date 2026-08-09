<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin;

/**
 * Json
 *
 * Provides JSON functions in an object oriented way.
 */
class Json
{
    /**
     * Returns a string containing the JSON representation of the given input
     *
     * @access public
     * @static
     * @param  mixed $input
     * @throws JsonException
     * @return string
     */
    public static function encode(&$input)
    {
        return json_encode($input, JSON_THROW_ON_ERROR);
    }

    /**
     * Returns an array with the contents as described in the given JSON input
     *
     * @access public
     * @static
     * @param  string $input
     * @throws JsonException
     * @return mixed
     */
    public static function decode(&$input)
    {
        return json_decode($input, true, 10, JSON_THROW_ON_ERROR);
    }
}
