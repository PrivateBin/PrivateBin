<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.15
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
        $i = 0;
        $iec = array('B', 'kiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        while ( ( $size / 1024 ) > 1 ) {
                $size = $size / 1024;
                $i++;
        }
        return number_format($size, 2, ".", " ") . ' ' . $iec[$i];
    }
}
