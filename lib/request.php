<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.22
 */

/**
 * request
 *
 * parses request parameters and provides helper functions for routing
*/
class request
{
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
        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
        {
            $_POST   = array_map('filter::stripslashes_deep', $_POST);
            $_GET    = array_map('filter::stripslashes_deep', $_GET);
            $_COOKIE = array_map('filter::stripslashes_deep', $_COOKIE);
        }

        // decide if we are in JSON API or HTML context
        if (
            (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] == 'JSONHttpRequest') ||
            (array_key_exists('HTTP_ACCEPT', $_SERVER) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        )
        {
            $this->_isJsonApi = true;
        }

        // parse parameters, depending on request type
        switch (array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET')
        {
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
        )
        {
            $this->_params['pasteid'] = $_SERVER['QUERY_STRING'];
        }

        // prepare operation, depending on current parameters
        if (
            (array_key_exists('data', $this->_params) && !empty($this->_params['data'])) ||
            (array_key_exists('attachment', $this->_params) && !empty($this->_params['attachment']))
        )
        {
            $this->_operation = 'create';
        }
        elseif (array_key_exists('pasteid', $this->_params) && !empty($this->_params['pasteid']))
        {
            if (array_key_exists('deletetoken', $this->_params) && !empty($this->_params['deletetoken']))
            {
                $this->_operation = 'delete';
            }
            else
            {
                $this->_operation = 'read';
            }
        }
        elseif (array_key_exists('jsonld', $this->_params) && !empty($this->_params['jsonld']))
        {
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
}