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
 * ShlinkProxy
 *
 * Forwards a URL for shortening to shlink and stores the result.
 */
class ShlinkProxy
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

        $shlink_api_url = $conf->getKey('apiurl', 'shlink');
        $shlink_api_key = $conf->getKey('apikey', 'shlink');

        if (empty($shlink_api_url) || empty($shlink_api_key)) {
            $this->_error = 'Error calling Shlink. Probably a configuration issue, like wrong or missing "apiurl" or "apikey".';
            return;
        }

        $data = file_get_contents(
            $shlink_api_url, false, stream_context_create(
                array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/json\r\n" .
                                     "X-Api-Key: " . $shlink_api_key . "\r\n",
                        'content' => json_encode(
                            array(
                                'longUrl'       => $link,
                            )
                        ),
                    ),
                )
            )
        );
        if ($data === false) {
            $http_response_header = $http_response_header ?? [];
            $statusCode = '';
            if (!empty($http_response_header) && preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[0], $matches)) {
                $statusCode = $matches[1];
            }
            $this->_error = 'Error calling shlink. HTTP request failed for URL ' . $shlink_api_url . '. Status code: ' . $statusCode;
            error_log('Error calling shlink: HTTP request failed for URL ' . $shlink_api_url . '. Status code: ' . $statusCode);
            return;
        }
        try {
            $data = Json::decode($data);
        } catch (Exception $e) {
            $this->_error = 'Error calling shlink. Probably a configuration issue, like wrong or missing "apiurl" or "apikey".';
            error_log('Error calling shlink: ' . $e->getMessage());
            return;
        }

        if (
            !is_null($data) &&
            // array_key_exists('statusCode', $data) &&
            // $data['statusCode'] == 200 &&
            array_key_exists('shortUrl', $data)
        ) {
            $this->_url = $data['shortUrl'];
        } else {
            $this->_error = 'Error parsing shlink response.';
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
