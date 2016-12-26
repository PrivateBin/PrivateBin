<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.1
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
     * @return void
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
     * @return void
     */
    public function draw($template)
    {
        $path = PATH . 'tpl' . DIRECTORY_SEPARATOR . $template . '.php';
        if (!file_exists($path)) {
            throw new Exception('Template ' . $template . ' not found!', 80);
        }
        extract($this->_variables);
        include $path;
    }
}
