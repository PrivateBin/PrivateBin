<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.1
 */

namespace PrivateBin;

/**
 * Request
 *
 * parses request parameters and provides helper functions for routing
 */
class Request
{
    /**
     * MIME type for JSON
     *
     * @const string
     */
    const MIME_JSON = 'application/json';

    /**
     * MIME type for HTML
     *
     * @const string
     */
    const MIME_HTML = 'text/html';

    /**
     * MIME type for XHTML
     *
     * @const string
     */
    const MIME_XHTML = 'application/xhtml+xml';

    /**
     * Input stream to use for PUT parameter parsing.
     *
     * @access private
     * @var string
     */
    private static $_inputStream = 'php://input';

    /**
     * Operation to perform.
     *
     * @access private
     * @var string
     */
    private $_operation = 'view';

    /**
     * Request parameters.
     *
     * @access private
     * @var array
     */
    private $_params = array();

    /**
     * If we are in a JSON API context.
     *
     * @access private
     * @var bool
     */
    private $_isJsonApi = false;

    /**
     * Constructor.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        // in case stupid admin has left magic_quotes enabled in php.ini (for PHP < 5.4)
        if (version_compare(PHP_VERSION, '5.4.0') < 0 && get_magic_quotes_gpc()) {
            $_POST   = array_map('PrivateBin\\Filter::stripslashesDeep', $_POST);
            $_GET    = array_map('PrivateBin\\Filter::stripslashesDeep', $_GET);
            $_COOKIE = array_map('PrivateBin\\Filter::stripslashesDeep', $_COOKIE);
        }

        // decide if we are in JSON API or HTML context
        $this->_isJsonApi = $this->_detectJsonRequest();

        // parse parameters, depending on request type
        switch (array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET') {
            case 'DELETE':
            case 'PUT':
                parse_str(file_get_contents(self::$_inputStream), $this->_params);
                break;
            case 'POST':
                $this->_params = $_POST;
                break;
            default:
                $this->_params = $_GET;
        }
        if (
            !array_key_exists('pasteid', $this->_params) &&
            !array_key_exists('jsonld', $this->_params) &&
            array_key_exists('QUERY_STRING', $_SERVER) &&
            !empty($_SERVER['QUERY_STRING'])
        ) {
            $this->_params['pasteid'] = $_SERVER['QUERY_STRING'];
        }

        // prepare operation, depending on current parameters
        if (
            (array_key_exists('data', $this->_params) && !empty($this->_params['data'])) ||
            (array_key_exists('attachment', $this->_params) && !empty($this->_params['attachment']))
        ) {
            $this->_operation = 'create';
        } elseif (array_key_exists('pasteid', $this->_params) && !empty($this->_params['pasteid'])) {
            if (array_key_exists('deletetoken', $this->_params) && !empty($this->_params['deletetoken'])) {
                $this->_operation = 'delete';
            } else {
                $this->_operation = 'read';
            }
        } elseif (array_key_exists('jsonld', $this->_params) && !empty($this->_params['jsonld'])) {
            $this->_operation = 'jsonld';
        }
    }

    /**
     * Get current operation.
     *
     * @access public
     * @return string
     */
    public function getOperation()
    {
        return $this->_operation;
    }

    /**
     * Get a request parameter.
     *
     * @access public
     * @param  string $param
     * @param  string $default
     * @return string
     */
    public function getParam($param, $default = '')
    {
        return array_key_exists($param, $this->_params) ? $this->_params[$param] : $default;
    }

    /**
     * If we are in a JSON API context.
     *
     * @access public
     * @return bool
     */
    public function isJsonApiCall()
    {
        return $this->_isJsonApi;
    }

    /**
     * Override the default input stream source, used for unit testing.
     *
     * @param string $input
     */
    public static function setInputStream($input)
    {
        self::$_inputStream = $input;
    }

    /**
     * detect the clients supported media type and decide if its a JSON API call or not
     *
     * Adapted from: https://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @access private
     * @return bool
     */
    private function _detectJsonRequest()
    {
        $hasAcceptHeader = array_key_exists('HTTP_ACCEPT', $_SERVER);
        $acceptHeader    = $hasAcceptHeader ? $_SERVER['HTTP_ACCEPT'] : '';

        // simple cases
        if (
            (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) &&
                $_SERVER['HTTP_X_REQUESTED_WITH'] == 'JSONHttpRequest') ||
            ($hasAcceptHeader &&
                strpos($acceptHeader, self::MIME_JSON) !== false &&
                strpos($acceptHeader, self::MIME_HTML) === false &&
                strpos($acceptHeader, self::MIME_XHTML) === false)
        ) {
            return true;
        }

        // advanced case: media type negotiation
        $mediaTypes = array();
        if ($hasAcceptHeader) {
            $mediaTypeRanges = explode(',', trim($acceptHeader));
            foreach ($mediaTypeRanges as $mediaTypeRange) {
                if (preg_match(
                    '#(\*/\*|[a-z\-]+/[a-z\-+*]+(?:\s*;\s*[^q]\S*)*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?#',
                    trim($mediaTypeRange), $match
                )) {
                    if (!isset($match[2])) {
                        $match[2] = '1.0';
                    } else {
                        $match[2] = (string) floatval($match[2]);
                    }
                    if (!isset($mediaTypes[$match[2]])) {
                        $mediaTypes[$match[2]] = array();
                    }
                    $mediaTypes[$match[2]][] = strtolower($match[1]);
                }
            }
            krsort($mediaTypes);
            foreach ($mediaTypes as $acceptedQuality => $acceptedValues) {
                if ($acceptedQuality === 0.0) {
                    continue;
                }
                foreach ($acceptedValues as $acceptedValue) {
                    if (
                        strpos($acceptedValue, self::MIME_HTML) === 0 ||
                        strpos($acceptedValue, self::MIME_XHTML) === 0
                    ) {
                        return false;
                    } elseif (strpos($acceptedValue, self::MIME_JSON) === 0) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
