<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.4.0
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
        if (strpos($link, $conf->getKey('basepath') . '/?') === false) {
            $this->_error = 'Trying to shorten a URL that isn\'t pointing at our instance.';
            return;
        }

        $data = file_get_contents(
            $conf->getKey('apiurl', 'yourls'), false, stream_context_create(
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
        if ($data === false || !is_string($data)) {
            $this->_error = 'Error calling YOURLS. Probably a configuration issue, like wrong or missing "apiurl" or "signature".';
            return;
        }

        try {
            $data = Json::decode($data);
        } catch (Exception $e) {
            $this->_error = $e->getMessage();
            return;
        }

        if (
            !is_null($data) &&
            array_key_exists('statusCode', $data) &&
            array_key_exists('shorturl', $data) &&
            $data['statusCode'] == 200
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
