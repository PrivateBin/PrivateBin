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
 * AbstractProxy
 *
 * Forwards a URL for shortening to shlink and stores the result.
 */
abstract class AbstractProxy
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
     * initializes and runs the proxy class
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

        $data = $this->_getcontents($conf, $link);

        if ($data == null) {
            $this->_error = 'Error calling proxy. Probably a configuration issue';
            error_log('Error calling proxy: ' . $this->_error);
            return;
        }

        if ($data === false) {
            $http_response_header = $http_response_header ?? array();
            $statusCode           = '';
            if (!empty($http_response_header) && preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[0], $matches)) {
                $statusCode = $matches[1];
            }
            $this->_error = 'Error calling proxy. HTTP request failed. Status code: ' . $statusCode;
            error_log('Error calling proxy: ' . $this->_error);
            return;
        }

        try {
            $data = Json::decode($data);
        } catch (Exception $e) {
            $this->_error = 'Error calling proxy. Probably a configuration issue, like wrong or missing config keys.';
            error_log('Error calling proxy: ' . $e->getMessage());
            return;
        }

        $url = $this->_extractShortUrl($data);

        if ($url === null) {
            $this->_error = 'Error calling proxy. Probably a configuration issue, like wrong or missing config keys.';
        } else {
            $this->_url = $url;
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

    /**
     * Abstract method to get contents from a URL.
     *
     * @param Configuration $conf
     * @param string $link
     * @return mixed
     */
    abstract protected function _getcontents(Configuration $conf, string $link);

    /**
     * Abstract method to extract the shortUrl from the response
     *
     * @param array $data
     * @return ?string
     */
    abstract protected function _extractShortUrl(array $data): ?string;
}
