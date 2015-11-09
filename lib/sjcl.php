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
        $accepted_keys = array('iv','v','iter','ks','ts','mode','adata','cipher','salt','ct');

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
            if (!array_key_exists($k, $decoded)) return false;
        }

        // Make sure some fields are base64 data.
        if (!base64_decode($decoded['iv'], true)) return false;
        if (!base64_decode($decoded['salt'], true)) return false;
        if (!($ct = base64_decode($decoded['ct'], true))) return false;

        // Make sure some fields have a reasonable size.
        if (strlen($decoded['iv']) > 24) return false;
        if (strlen($decoded['salt']) > 14) return false;

        // Make sure some fields contain no unsupported values.
        if (!(is_int($decoded['v']) || is_float($decoded['v'])) || (float) $decoded['v'] < 1) return false;
        if (!is_int($decoded['iter']) || $decoded['iter'] <= 100) return false;
        if (!in_array($decoded['ks'], array(128, 192, 256), true)) return false;
        if (!in_array($decoded['ts'], array(64, 96, 128), true)) return false;
        if (!in_array($decoded['mode'], array('ccm', 'ocb2', 'gcm'), true)) return false;
        if ($decoded['cipher'] !== 'aes') return false;

        // Reject data if entropy is too low
        if (strlen($ct) > strlen(gzdeflate($ct))) return false;

        return true;
    }
}
