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
 * YourlsProxy
 *
 * Forwards a URL for shortening to YOURLS (your own URL shortener) and stores
 * the result.
 */
class YourlsProxy extends AbstractProxy
{
    /**
     * constructor
     *
     * initializes and runs YourlsProxy
     *
     * @access public
     * @param string $link
     */
    public function __construct(Configuration $conf, $link)
    {
        parent::__construct($conf, $link);
    }

    /**
     * Overrides the abstract parent function to get contents from YOURLS API.
     *
     * @access protected
     * @return string
     */
    protected function _getcontents(Configuration $conf, string $link)
    {
        $yourls_api_url = $conf->getKey('apiurl', 'yourls');

        if (empty($yourls_api_url)) {
            return null;
        }

        return file_get_contents(
            $yourls_api_url, false, stream_context_create(
                array(
                    'http' => array(
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
                    ),
                )
            )
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
            !is_null($data) &&
            array_key_exists('statusCode', $data) &&
            $data['statusCode'] == 200 &&
            array_key_exists('shorturl', $data)
        ) {
            return $data['shorturl'];
        }
        return null;
    }
}
