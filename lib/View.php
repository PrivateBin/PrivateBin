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
 * View
 *
 * Displays the templates
 */
class View
{
    /**
     * variables available in the template
     *
     * @access private
     * @var    array
     */
    private $_variables = array();

    /**
     * assign variables to be used inside of the template
     *
     * @access public
     * @param  string $name
     * @param  mixed  $value
     */
    public function assign($name, $value)
    {
        $this->_variables[$name] = $value;
    }

    /**
     * render a template
     *
     * @access public
     * @param  string $template
     * @throws Exception
     */
    public function draw($template)
    {
        $file = substr($template, 0, 10) === 'bootstrap-' ? 'bootstrap' : $template;
        $path = PATH . 'tpl' . DIRECTORY_SEPARATOR . $file . '.php';
        if (!file_exists($path)) {
            throw new Exception('Template ' . $template . ' not found!', 80);
        }
        extract($this->_variables);
        include $path;
    }

    /**
     * echo script tag incl. SRI hash for given script file
     *
     * @access private
     * @param  string $file
     * @param  string $attributes additional attributes to add into the script tag
     */
    private function _scriptTag($file, $attributes = '')
    {
        $sri = array_key_exists($file, $this->_variables['SRI']) ?
            ' integrity="' . $this->_variables['SRI'][$file] . '"' : '';
        // if the file isn't versioned (ends in a digit), add our own version
        $cacheBuster = ctype_digit(substr($file, -4, 1)) ?
            '' : '?' . rawurlencode($this->_variables['VERSION']);
        echo '<script ', $attributes,
        ' type="text/javascript" data-cfasync="false" src="', $file,
        $cacheBuster, '"', $sri, ' crossorigin="anonymous"></script>', PHP_EOL;
    }
}
