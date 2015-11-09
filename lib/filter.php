<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.22
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
     * format a given time string into a human readable label (localized)
     *
     * accepts times in the format "[integer][time unit]"
     *
     * @access public
     * @static
     * @param  string $time
     * @throws Exception
     * @return string
     */
    public static function time_humanreadable($time)
    {
        if (preg_match('/^(\d+) *(\w+)$/', $time, $matches) !== 1) {
            throw new Exception("Error parsing time format '$time'", 30);
        }
        switch ($matches[2]) {
            case 'sec':
                $unit = 'second';
                break;
            case 'min':
                $unit = 'minute';
                break;
            default:
                $unit = rtrim($matches[2], 's');
        }
        return i18n::_(array('%d ' . $unit, '%d ' . $unit . 's'), (int) $matches[1]);
    }

    /**
     * format a given number of bytes in IEC 80000-13:2008 notation (localized)
     *
     * @access public
     * @static
     * @param  int $size
     * @return string
     */
    public static function size_humanreadable($size)
    {
        $iec = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        $i = 0;
        while ( ( $size / 1024 ) >= 1 ) {
                $size = $size / 1024;
                $i++;
        }
        return number_format($size, ($i ? 2 : 0), '.', ' ') . ' ' . i18n::_($iec[$i]);
    }

    /**
     * fixed time string comparison operation to prevent timing attacks
     * https://crackstation.net/hashing-security.htm?=rd#slowequals
     *
     * @access public
     * @static
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
