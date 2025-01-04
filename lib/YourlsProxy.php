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

use Exception;

/**
 * YourlsProxy
 *
 * Forwards a URL for shortening to YOURLS (your own URL shortener) and stores
 * the result.
 */
class YourlsProxy
{
    /**
     * error message
     *
     * @access private
     * @var    string
     */
    private $_error = '';

    /**
     * shortened URL
     *
     * @access private
     * @var    string
     */
    private $_url = '';

    /**
     * constructor
     *
     * initializes and runs PrivateBin
     *
     * @access public
     * @param string $link
     */
    public function __construct(Configuration $conf, $link)
    {
        if (!str_starts_with($link, $conf->getKey('basepath') . '?')) {
            $this->_error = 'Trying to shorten a URL that isn\'t pointing at our instance.';
            return;
        }

        $yourls_api_url = $conf->getKey('apiurl', 'yourls');
        if (empty($yourls_api_url)) {
            $this->_error = 'Error calling YOURLS. Probably a configuration issue, like wrong or missing "apiurl" or "signature".';
            return;
        }

        $data = file_get_contents(
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
        try {
            $data = Json::decode($data);
        } catch (Exception $e) {
            $this->_error = 'Error calling YOURLS. Probably a configuration issue, like wrong or missing "apiurl" or "signature".';
            error_log('Error calling YOURLS: ' . $e->getMessage());
            return;
        }

        if (
            !is_null($data) &&
            array_key_exists('statusCode', $data) &&
            $data['statusCode'] == 200 &&
            array_key_exists('shorturl', $data)
        ) {
            $this->_url = $data['shorturl'];
        } else {
            $this->_error = 'Error parsing YOURLS response.';
        }
    }

    /**
     * Returns the (untranslated) error message
     *
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns the shortened URL
     *
     * @access public
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Returns true if any error has occurred
     *
     * @access public
     * @return bool
     */
    public function isError()
    {
        return !empty($this->_error);
    }
}
