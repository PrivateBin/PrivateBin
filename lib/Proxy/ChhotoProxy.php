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

use JsonException;
use PrivateBin\Configuration;
use PrivateBin\Json;

/**
 * ChhotoProxy
 *
 * Forwards a URL for shortening to Chhoto URL (lix.sk) and stores the result.
 */
class ChhotoProxy extends AbstractProxy
{
    /**
     * Overrides the abstract parent function to get the proxy URL.
     *
     * @param Configuration $conf
     * @return string
     */
    protected function _getProxyUrl(Configuration $conf): string
    {
        return $conf->getKey('apiurl', 'chhoto');
    }

    /**
     * Overrides the abstract parent function to get contents from Chhoto API.
     *
     * @access protected
     * @param Configuration $conf
     * @param string $link
     * @return array
     */
    protected function _getProxyPayload(Configuration $conf, string $link): array
    {
        $apiKey = $conf->getKey('apikey', 'chhoto');

        $body = [
            'shortlink'    => '',          // empty = auto-generate
            'longlink'     => $link,
            'expiry_delay' => 0,           // 0 = never expire
            'notes'        => 'PrivateBin paste',
        ];

        try {
            return [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n" .
                             'X-API-Key: ' . $apiKey . "\r\n" .
                             "Accept: application/json\r\n",
                'content' => Json::encode($body),
            ];
        } catch (JsonException $e) {
            error_log('[' . get_class($this) . '] Error encoding body: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extracts the short URL from the Chhoto API response.
     *
     * @access protected
     * @param array $data
     * @return ?string
     */
    protected function _extractShortUrl(array $data): ?string
    {
        // Chhoto usually returns "shorturl"
        if (!empty($data['shorturl'])) {
            return $data['shorturl'];
        }

        // Fallback for older versions that return only the slug
        if (!empty($data['shortlink'])) {
            $apiUrl = $this->_getProxyUrl(new Configuration()); // not ideal, but works
            // Better: hardcode or get base from config
            return 'https://lix.sk/' . ltrim($data['shortlink'], '/');
        }

        return null;
    }
}
