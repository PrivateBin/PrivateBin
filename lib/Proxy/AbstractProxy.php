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

use Exception;
use PrivateBin\Configuration;
use PrivateBin\Json;

/**
 * AbstractProxy
 *
 * Forwards a URL for shortening and stores the result.
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
     * @param Configuration $conf
     * @param string $link
     */
    public function __construct(Configuration $conf, string $link)
    {
        if (!filter_var($link, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED | FILTER_FLAG_QUERY_REQUIRED)) {
            $this->_error = 'Invalid URL given.';
            return;
        }

        if (!str_starts_with($link, $conf->getKey('basepath') . '?') ||
            parse_url($link, PHP_URL_HOST) != parse_url($conf->getKey('basepath'), PHP_URL_HOST)
        ) {
            $this->_error = 'Trying to shorten a URL that isn\'t pointing at our instance.';
            return;
        }

        $proxyUrl = $this->_getProxyUrl($conf);

        if (empty($proxyUrl)) {
            $this->_error = 'Proxy error: Proxy URL is empty. This can be a configuration issue, like wrong or missing config keys.';
            $this->logErrorWithClassName($this->_error);
            return;
        }

        $data = file_get_contents($proxyUrl, false,
            stream_context_create(
                array(
                    'http' => $this->_getProxyPayload($conf, $link),
                )
            )
        );

        if ($data === false) {
            $http_response_header = $http_response_header ?? array();
            $statusCode           = '';
            if (!empty($http_response_header) && preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[0], $matches)) {
                $statusCode = $matches[1];
            }
            $this->_error = 'Proxy error: Bad response. This can be a configuration issue, like wrong or missing config keys or a temporary outage.';
            $this->logErrorWithClassName($this->_error . ' Status code: ' . $statusCode);
            return;
        }

        try {
            $jsonData = Json::decode($data);
        } catch (Exception $e) {
            $this->_error = 'Proxy error: Error parsing proxy response. This can be a configuration issue, like wrong or missing config keys.';
            $this->logErrorWithClassName('Error calling proxy: ' . $e->getMessage());
            return;
        }

        $url = $this->_extractShortUrl($jsonData);

        if ($url === null || empty($url)) {
            $this->_error = 'Proxy error: Error parsing proxy response. This can be a configuration issue, like wrong or missing config keys.';
            $this->logErrorWithClassName('Error calling proxy: ' . $data);
        } else {
            $this->_url = $url;
        }
    }

    private function logErrorWithClassName(string $error)
    {
        error_log('[' . get_class($this) . '] ' . $error);
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
     * Abstract method to get the payload to send to the URL shortener
     *
     * @access protected
     * @param Configuration $conf
     * @param string $link
     * @return array
     */
    abstract protected function _getProxyPayload(Configuration $conf, string $link): array;

    /**
     * Abstract method to extract the shortUrl from the response
     *
     * @param array $data
     * @return ?string
     */
    abstract protected function _extractShortUrl(array $data): ?string;

    /**
     * Abstract method to get the proxy URL
     *
     * @param Configuration $conf
     * @return string
     */
    abstract protected function _getProxyUrl(Configuration $conf): string;
}
