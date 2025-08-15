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
 * ShlinkProxy
 *
 * Forwards a URL for shortening to shlink and stores the result.
 */
class ShlinkProxy extends AbstractProxy
{
    /**
     * constructor
     *
     * initializes and runs ShlinkProxy
     *
     * @access public
     * @param string $link
     */
    public function __construct(Configuration $conf, $link)
    {
        parent::__construct($conf, $link);
    }

    /**
     * Overrides the abstract parent function to get contents from Shlink API.
     *
     * @access protected
     * @return string
     */
    protected function _getcontents(Configuration $conf, string $link)
    {
        $shlink_api_url = $conf->getKey('apiurl', 'shlink');
        $shlink_api_key = $conf->getKey('apikey', 'shlink');

        if (empty($shlink_api_url) || empty($shlink_api_key)) {
            return;
        }

        $body = array(
            'longUrl' => $link,
        );

        return file_get_contents(
            $shlink_api_url, false, stream_context_create(
                array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/json\r\n" .
                                     'X-Api-Key: ' . $shlink_api_key . "\r\n",
                        'content' => Json::encode($body),
                    ),
                )
            )
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
            !is_null($data) &&
            array_key_exists('shortUrl', $data)
        ) {
            return $data['shortUrl'];
        }
        return null;
    }
}
