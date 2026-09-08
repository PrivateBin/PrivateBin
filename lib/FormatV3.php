<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin;

/**
 * Validates version 3 recipient-encrypted pastes.
 *
 * Version 3 retains the version 2 AES-GCM payload and adds an authenticated
 * ML-KEM-768 content-key envelope at adata[4]. Comments continue to use v2
 * after the recipient has recovered the paste content key.
 */
class FormatV3
{
    private const SUITE = 'ML-KEM-768+HKDF-SHA-256+AES-256-GCM';

    /**
     * @param array $message
     * @param bool  $isComment
     * @return bool
     */
    public static function isValid(&$message, $isComment = false)
    {
        if ($isComment ||
            !array_key_exists('v', $message) ||
            !(is_int($message['v']) || is_float($message['v'])) ||
            (float) $message['v'] !== 3.0 ||
            !is_array($message['adata'] ?? null) ||
            count($message['adata']) !== 5) {
            return false;
        }

        // Reuse the established payload checks with only the version changed.
        $version2 = $message;
        $version2['v'] = 2;
        if (!FormatV2::isValid($version2, false)) {
            return false;
        }

        $envelope = $message['adata'][4];
        $required = ['type', 'suite', 'kid', 'kemct', 'salt', 'iv', 'wrappedkey'];
        if (!is_array($envelope) || count($envelope) !== count($required)) {
            return false;
        }
        foreach ($required as $key) {
            if (!array_key_exists($key, $envelope) || !is_string($envelope[$key])) {
                return false;
            }
        }
        if ($envelope['type'] !== 'recipient' ||
            $envelope['suite'] !== self::SUITE ||
            preg_match('/^[A-Za-z0-9_-]{16}$/D', $envelope['kid']) !== 1) {
            return false;
        }

        return self::isBase64Length($envelope['kemct'], 1088) &&
            self::isBase64Length($envelope['salt'], 32) &&
            self::isBase64Length($envelope['iv'], 12) &&
            self::isBase64Length($envelope['wrappedkey'], 48);
    }

    /**
     * Require canonical padded base64 with the exact decoded byte length.
     */
    private static function isBase64Length($value, $length)
    {
        $decoded = base64_decode($value, true);
        return $decoded !== false && strlen($decoded) === $length && base64_encode($decoded) === $value;
    }
}
