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

// change this, if your php files and data is outside of your webservers document root
define('PATH', '');

define('PUBLIC_PATH', __DIR__);

// auto-redirect to installer if conf.php is missing and install/ exists
$confPath = PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php';
if (!is_readable($confPath) && is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'install')) {
    header('Location: install/');
    exit;
}

require PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
new PrivateBin\Controller;
