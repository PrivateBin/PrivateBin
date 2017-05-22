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

namespace PrivateBin\Persistence;

/**
 * WebServer
 *
 * Provides detect webserver functions.
 */
class WebServer extends AbstractPersistence
{
    /**
     * path in which to persist something
     *
     * @access private
     * @static
     * @var    string
     */
    private static $_path = 'data';

    /**
     * key to detect server software
     *
     * @access private
     * @static
     * @var    string
     */
    private static $_serverKey = 'SERVER_SOFTWARE';

    /**
     * get a webserver
     *
     * @access public
     * @static
     * @return array
     */
    public static function getWebserver()
    {
        $regex = "/(?<software>\w+)\/(?<version>[0-9.a-z]*)/";

        if(isset($_SERVER[self::$_serverKey]) && preg_match_all($regex, $_SERVER[self::$_serverKey], $arr))
            return array_merge(['software' => $arr['software'][0]], ['version' => $arr['version'][0]]);
        else
            return array();
    }

    /**
     * Write a directive into .htacess
     *
     *
     * @access public
     * @static
     * @throws Exception
     */
    public static function canHtaccess()
    {
        $file = '.htaccess';
        if (is_dir(self::$_path) && !is_file($file)) {
            $server = self::getWebserver();
            if($server['software'] == "Apache") {
                if (version_compare($server['version'], '2.2') >= 0) {
                    self::_store(
                        $file,
                        'Allow from none' . PHP_EOL .
                        'Deny from all' . PHP_EOL,
                        LOCK_EX
                    );
                } else {
                    self::_store(
                        $file,
                        'Require all denied' . PHP_EOL,
                        LOCK_EX
                    );
                }
            }
        }
    }
}
