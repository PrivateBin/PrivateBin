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
 * FormatV3
 *
 * Provides validation function for version 3 format of pastes & comments.
 * Extends FormatV2 to inherit base validation, adds PQC-specific checks.
 *
 * Version 3 adds post-quantum cryptography (ML-KEM / Kyber-768) support.
 * The 'kem' object contains KEM ciphertext and private key (stored unencrypted).
 * Security comes from urlKey in URL fragment, not from encrypting KEM keys.
 */
class FormatV3 extends FormatV2
{
    /**
     * version 3 format validator
     *
     * Checks if the given array is a proper version 3 formatted, encrypted message.
     * Validates base v2 structure plus PQC-specific kem object.
     *
     * @access public
     * @static
     * @param  array $message
     * @param  bool  $isComment
     * @return bool
     */
    public static function isValid(&$message, $isComment = false)
    {
        // First validate v2 structure (parent class)
        // Note: This will fail because v3 has additional 'kem' field
        // So we need custom validation here

        $required_keys = array('adata', 'v', 'ct');
        if ($isComment) {
            $required_keys[] = 'pasteid';
            $required_keys[] = 'parentid';
        } else {
            $required_keys[] = 'meta';
            $required_keys[] = 'kem'; // v3 specific: KEM object for pastes
        }

        // Make sure no additional keys were added (except kem for v3 pastes).
        $message_keys = array_keys($message);
        sort($message_keys);
        sort($required_keys);
        if ($message_keys !== $required_keys) {
            return false;
        }

        // Make sure required fields are present.
        foreach ($required_keys as $k) {
            if (!array_key_exists($k, $message)) {
                return false;
            }
        }

        // Version must be >= 3
        if (!(is_int($message['v']) || is_float($message['v'])) || (float) $message['v'] < 3) {
            return false;
        }

        // Make sure adata is an array.
        if (!is_array($message['adata'])) {
            return false;
        }

        $cipherParams = $isComment ? $message['adata'] : $message['adata'][0];

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
        // - iterations, refuse less then 10000 iterations (minimum NIST recommendation)
        if (!is_int($cipherParams[2]) || $cipherParams[2] <= 10000) {
            return false;
        }
        // - key size
        if (!in_array($cipherParams[3], array(128, 192, 256), true)) {
            return false;
        }
        // - tag size
        if (!in_array($cipherParams[4], array(64, 96, 128), true)) {
            return false;
        }
        // - algorithm, must be AES
        if ($cipherParams[5] !== 'aes') {
            return false;
        }
        // - mode
        if (!in_array($cipherParams[6], array('ctr', 'cbc', 'gcm'), true)) {
            return false;
        }
        // - compression
        if (!in_array($cipherParams[7], array('zlib', 'none'), true)) {
            return false;
        }

        // Reject data if entropy is too low
        if (strlen($ct) > strlen(gzdeflate($ct))) {
            return false;
        }

        // require only the key 'expire' in the metadata of pastes
        if (!$isComment && (
            count($message['meta']) === 0 ||
            !array_key_exists('expire', $message['meta']) ||
            count($message['meta']) > 1
        )) {
            return false;
        }

        // V3-specific validation: KEM object required for pastes (not for comments yet)
        if (!$isComment) {
            if (!isset($message['kem']) || !is_array($message['kem'])) {
                return false;
            }

            $kem = $message['kem'];

            // Validate KEM algorithm family
            if (!isset($kem['algo']) || !is_string($kem['algo'])) {
                return false;
            }

            // Validate algorithm is supported (currently only kyber768)
            $supportedAlgos = array('kyber768');
            if (!in_array($kem['algo'], $supportedAlgos, true)) {
                return false;
            }

            // Validate KEM parameter set
            if (!isset($kem['param']) || !is_string($kem['param'])) {
                return false;
            }

            // Validate KEM ciphertext (base64)
            if (!isset($kem['ciphertext']) || !self::isBase64($kem['ciphertext'])) {
                return false;
            }

            // Validate KEM private key (base64)
            if (!isset($kem['privkey']) || !self::isBase64($kem['privkey'])) {
                return false;
            }

            // Validate reasonable sizes for Kyber-768
            // Ciphertext should be around 1088 bytes (base64 ~1450 chars)
            $ctLen = strlen($kem['ciphertext']);
            if ($ctLen < 1000 || $ctLen > 2000) {
                return false;
            }

            // Private key should be around 2400 bytes (base64 ~3200 chars)
            $pkLen = strlen($kem['privkey']);
            if ($pkLen < 2500 || $pkLen > 5000) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if string is valid base64
     *
     * @access private
     * @static
     * @param  string $str
     * @return bool
     */
    private static function isBase64($str)
    {
        return base64_decode($str, true) !== false;
    }
}
