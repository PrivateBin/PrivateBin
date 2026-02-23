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

namespace PrivateBin\Exception;

use Exception;

/**
 * JsonException
 *
 * An Exception representing JSON en- or decoding errors.
 */
class JsonException extends Exception
{
    /**
     * Exception constructor with mandatory JSON error code.
     *
     * @access public
     * @param  int $code
     */
    public function __construct(int $code)
    {
        $message = 'A JSON error occurred';
        if (function_exists('json_last_error_msg')) {
            $message .= ': ' . json_last_error_msg();
        }
        $message .= ' (' . $code . ')';
        parent::__construct($message, 90);
    }
}
