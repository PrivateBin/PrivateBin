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
 * sjcl
 *
 * Provides SJCL validation function.
 */
class sjcl
{
    /**
     * SJCL validator
     *
     * Checks if a json string is a proper SJCL encrypted message.
     *
     * @access public
     * @static
     * @param  string $encoded JSON
     * @return bool
     */
    public static function isValid($encoded)
    {
        $accepted_keys = array('iv','salt','ct');

        // Make sure content is valid json
        $decoded = json_decode($encoded);
        if (is_null($decoded)) return false;
        $decoded = (array) $decoded;

        // Make sure no additionnal keys were added.
        if (
            count(array_keys($decoded)) != count($accepted_keys)
        ) return false;

        // Make sure required fields are present and contain base64 data.
        foreach($accepted_keys as $k)
        {
            if (!(
                array_key_exists($k, $decoded) &&
                $ct = base64_decode($decoded[$k], $strict=true)
            )) return false;
        }

        // Make sure some fields have a reasonable size.
        if (strlen($decoded['iv']) > 24) return false;
        if (strlen($decoded['salt']) > 14) return false;

        // Reject data if entropy is too low
        if (strlen($ct) > strlen(gzdeflate($ct))) return false;

        return true;
    }
}
