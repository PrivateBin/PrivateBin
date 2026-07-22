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
 * FormatV2
 *
 * Provides validation function for version 2 format of pastes & comments.
 */
class FormatV2
{
    /**
     * version 2 format validator
     *
     * Checks if the given array is a proper version 2 formatted, encrypted message.
     *
     * @access public
     * @static
     * @param  array $message
     * @param  bool  $isComment
     * @return bool
     */
    public static function isValid(&$message, $isComment = false)
    {
        $required_keys = ['adata', 'v', 'ct'];
        if ($isComment) {
            $required_keys[] = 'pasteid';
            $required_keys[] = 'parentid';
        } else {
            $required_keys[] = 'meta';
        }

        // Make sure no additionnal keys were added.
        if (count(array_keys($message)) !== count($required_keys)) {
            return false;
        }

        // Make sure required fields are present.
        foreach ($required_keys as $k) {
            if (!array_key_exists($k, $message)) {
                return false;
            }
        }

        // Make sure adata is an array.
        if (!is_array($message['adata'])) {
            return false;
        }

        $cipherParams = $isComment ? $message['adata'] : ($message['adata'][0] ?? null);

        // Make sure the cipher parameters are a properly sized array.
        if (!is_array($cipherParams) || count($cipherParams) < 8) {
            return false;
        }

        // Make sure the ciphertext and the cipher parameters used in the
        // string operations below are actually strings, so that malformed
        // input yields "Invalid data." instead of a fatal type error.
        if (!is_string($message['ct']) ||
            !is_string($cipherParams[0] ?? null) ||
            !is_string($cipherParams[1] ?? null)) {
            return false;
        }

        // Make sure some fields are base64 data:
        // - initialization vector
        if (!base64_decode($cipherParams[0], true)) {
            return false;
        }
        // - salt
        if (!base64_decode($cipherParams[1], true)) {
            return false;
        }
        // - cipher text
        if (!($ct = base64_decode($message['ct'], true))) {
            return false;
        }

        // Make sure some fields have a reasonable size:
        // - initialization vector
        if (strlen($cipherParams[0]) > 24) {
            return false;
        }
        // - salt
        if (strlen($cipherParams[1]) > 14) {
            return false;
        }

        // Make sure some fields contain no unsupported values:
        // - version
        if (!(is_int($message['v']) || is_float($message['v'])) || (float) $message['v'] < 2) {
            return false;
        }
        // - iterations, refuse less then 10000 iterations (minimum NIST recommendation)
        if (!is_int($cipherParams[2]) || $cipherParams[2] <= 10000) {
            return false;
        }
        // - key size
        if (!in_array($cipherParams[3], [128, 192, 256], true)) {
            return false;
        }
        // - tag size
        if (!in_array($cipherParams[4], [64, 96, 128], true)) {
            return false;
        }
        // - algorithm, must be AES
        if ($cipherParams[5] !== 'aes') {
            return false;
        }
        // - mode
        if (!in_array($cipherParams[6], ['ctr', 'cbc', 'gcm'], true)) {
            return false;
        }
        // - compression
        if (!in_array($cipherParams[7], ['zlib', 'none'], true)) {
            return false;
        }

        // Reject data if entropy is too low
        if (strlen($ct) > strlen(gzdeflate($ct))) {
            return false;
        }

        // require only the key 'expire' in the metadata of pastes
        if (!$isComment && (
            !is_array($message['meta']) ||
            count($message['meta']) === 0 ||
            !array_key_exists('expire', $message['meta']) ||
            count($message['meta']) > 1
        )) {
            return false;
        }

        return true;
    }
}
