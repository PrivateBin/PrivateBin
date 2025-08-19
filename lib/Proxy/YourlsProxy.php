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

/**
 * YourlsProxy
 *
 * Forwards a URL for shortening to YOURLS (your own URL shortener) and stores
 * the result.
 */
class YourlsProxy extends AbstractProxy
{
    /**
     * Overrides the abstract parent function to get the proxy URL.
     *
     * @param Configuration $conf
     * @return string
     */
    protected function _getProxyUrl(Configuration $conf): string
    {
        return $conf->getKey('apiurl', 'yourls');
    }

    /**
     * Overrides the abstract parent function to get contents from YOURLS API.
     *
     * @access protected
     * @param Configuration $conf
     * @param string $link
     * @return array
     */
    protected function _getProxyPayload(Configuration $conf, string $link): array
    {
        return array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query(
                array(
                    'signature' => $conf->getKey('signature', 'yourls'),
                    'format'    => 'json',
                    'action'    => 'shorturl',
                    'url'       => $link,
                )
            ),
        );
    }

    /**
     * Extracts the short URL from the YOURLS API response.
     *
     * @access protected
     * @param array $data
     * @return ?string
     */
    protected function _extractShortUrl(array $data): ?string
    {
        if (
            array_key_exists('statusCode', $data) &&
            $data['statusCode'] == 200 &&
            array_key_exists('shorturl', $data)
        ) {
            return $data['shorturl'];
        }
        return null;
    }
}
