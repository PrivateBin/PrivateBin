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
        $dir  = PATH . 'tpl' . DIRECTORY_SEPARATOR;
        $file = substr($template, 0, 10) === 'bootstrap-' ? 'bootstrap' : $template;
        $path = $dir . $file . '.php';
        if (!is_file($path)) {
            throw new Exception('Template ' . $template . ' not found in file ' . $path . '!', 80);
        }
        if (!in_array($path, glob($dir . '*.php', GLOB_NOSORT | GLOB_ERR), true)) {
            throw new Exception('Template ' . $file . '.php not found in ' . $dir . '!', 81);
        }
        extract($this->_variables);
        include $path;
    }

    /**
     * get cache buster query string
     *
     * if the file isn't versioned (ends in a digit), adds our own version as a query string
     *
     * @access private
     * @param  string $file
     */
    private function _getCacheBuster($file)
    {
        if ((bool) preg_match('#[0-9]\.m?js$#', (string) $file)) {
            return '';
        }
        return '?' . rawurlencode($this->_variables['VERSION']);
    }

    /**
     * get SRI hash for given file
     *
     * @access private
     * @param  string $file
     */
    private function _getSri($file)
    {
        if (array_key_exists($file, $this->_variables['SRI'])) {
            return ' integrity="' . $this->_variables['SRI'][$file] . '"';
        }
        return '';
    }

    /**
     * echo module preload link tag incl. SRI hash for given script file
     *
     * @access private
     * @param  string $file
     */
    private function _linkTag($file)
    {
        echo '<link rel="modulepreload" href="', $file,
        $this->_getCacheBuster($file), '"', $this->_getSri($file), ' />', PHP_EOL;
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
        echo '<script ', $attributes,
        ' type="text/javascript" data-cfasync="false" src="', $file,
        $this->_getCacheBuster($file), '"', $this->_getSri($file),
        ' crossorigin="anonymous"></script>', PHP_EOL;
    }
}
