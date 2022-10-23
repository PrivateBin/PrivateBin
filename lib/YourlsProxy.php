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

use PrivateBin\Configuration;

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
        if (strpos($link, $conf->getKey('basepath') . '/?') !== false) {
            // Init the CURL session
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $conf->getKey("apiurl", "yourls"));
            curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
            curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(     // Data to POST
                                'signature' => $conf->getKey("signature", "yourls"),
                                'format'   => 'json',
                                'action'   => 'shorturl',
                                'url' =>  $link
            ));
            // Fetch and return content
            $data = curl_exec($ch);
            curl_close($ch);

            if (!($data === FALSE) && is_string($data))
            {
                $data = json_decode( $data, true);

                if (!is_null($data) && array_key_exists('statusCode', $data)
                        && array_key_exists('shorturl', $data) &&  ($data['statusCode'] == 200))
                {
                    $this->_url = $data['shorturl'];
                    $opSuccess = TRUE;
                } else {
                    $this->_error = 'Error parsing YOURLS response.';
                }
            } else {
                $this->_error = 'Error calling YOURLS. Probably a configuration issue, like wrong or missing "apiurl" or "signature".';
            }
        } else {
            $this->_error = 'Trying to shorten a URL not pointing to our PrivateBin instance.';
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
