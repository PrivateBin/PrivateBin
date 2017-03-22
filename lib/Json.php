<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.1
 */

namespace PrivateBin;

use Exception;

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
     * @throws Exception
     * @return string
     */
    public static function encode($input)
    {
        $jsonString = json_encode($input);
        $errorCode  = json_last_error();
        if ($errorCode === JSON_ERROR_NONE) {
            return $jsonString;
        }

        $message = 'A JSON error occurred';
        if (function_exists('json_last_error_msg')) {
            $message .= ': ' . json_last_error_msg();
        }
        $message .= ' (' . $errorCode . ')';
        throw new Exception($message, 90);
    }
}
