<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.3.4
 */

// change this, if your php files and data is outside of your webservers document root
define('PATH', '');

define('PUBLIC_PATH', __DIR__);
require PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
new PrivateBin\Controller;
