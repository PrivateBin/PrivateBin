<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.19
 */

/**
 * filter
 *
 * Provides data filtering functions.
 */
class filter
{
    /**
     * strips slashes deeply
     *
     * @access public
     * @static
     * @param  mixed $value
     * @return mixed
     */
    public static function stripslashes_deep($value)
    {
        return is_array($value) ?
            array_map('filter::stripslashes_deep', $value) :
            stripslashes($value);
    }

    /**
     * format a given number of bytes
     *
     * @access public
     * @static
     * @param  int $size
     * @return string
     */
    public static function size_humanreadable($size)
    {
        $iec = array('B', 'kiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        $i = 0;
        while ( ( $size / 1024 ) >= 1 ) {
                $size = $size / 1024;
                $i++;
        }
        return number_format($size, ($i ? 2 : 0), '.', ' ') . ' ' . $iec[$i];
    }

    /**
     * validate paste ID
     *
     * @access public
     * @param  string $dataid
     * @return bool
     */
    public static function is_valid_paste_id($dataid)
    {
        return (bool) preg_match('#\A[a-f\d]{16}\z#', $dataid);
    }

    /**
     * fixed time string comparison operation to prevent timing attacks
     * https://crackstation.net/hashing-security.htm?=rd#slowequals
     *
     * @access public
     * @param  string $a
     * @param  string $b
     * @return bool
     */
    public static function slow_equals($a, $b)
    {
        $diff = strlen($a) ^ strlen($b);
        for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
        {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $diff === 0;
     }
}
