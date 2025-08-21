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

namespace PrivateBin\Proxy;

use PrivateBin\Configuration;
use PrivateBin\Json;

/**
 * ShlinkProxy
 *
 * Forwards a URL for shortening to shlink and stores the result.
 */
class ShlinkProxy extends AbstractProxy
{
    /**
     * Overrides the abstract parent function to get the proxy URL.
     *
     * @param Configuration $conf
     * @return string
     */
    protected function _getProxyUrl(Configuration $conf): string
    {
        return $conf->getKey('apiurl', 'shlink');
    }

    /**
     * Overrides the abstract parent function to get contents from Shlink API.
     *
     * @access protected
     * @param Configuration $conf
     * @param string $link
     * @return array
     */
    protected function _getProxyPayload(Configuration $conf, string $link): array
    {
        $shlink_api_key = $conf->getKey('apikey', 'shlink');

        $body = array(
            'longUrl' => $link,
        );

        return array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" .
                         'X-Api-Key: ' . $shlink_api_key . "\r\n",
            'content' => Json::encode($body),
        );
    }

    /**
     * Extracts the short URL from the shlink API response.
     *
     * @access protected
     * @param array $data
     * @return ?string
     */
    protected function _extractShortUrl(array $data): ?string
    {
        if (
            array_key_exists('shortUrl', $data)
        ) {
            return $data['shortUrl'];
        }
        return null;
    }
}
