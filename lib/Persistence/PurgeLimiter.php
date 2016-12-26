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

use PrivateBin\Configuration;

/**
 * PurgeLimiter
 *
 * Handles purge limiting, so purging is not triggered too frequently.
 */
class PurgeLimiter extends AbstractPersistence
{
    /**
     * time limit in seconds, defaults to 300s
     *
     * @access private
     * @static
     * @var    int
     */
    private static $_limit = 300;

    /**
     * set the time limit in seconds
     *
     * @access public
     * @static
     * @param  int $limit
     * @return void
     */
    public static function setLimit($limit)
    {
        self::$_limit = $limit;
    }

    /**
     * set configuration options of the traffic limiter
     *
     * @access public
     * @static
     * @param Configuration $conf
     * @return void
     */
    public static function setConfiguration(Configuration $conf)
    {
        self::setLimit($conf->getKey('limit', 'purge'));
        self::setPath($conf->getKey('dir', 'purge'));
    }

    /**
     * check if the purge can be performed
     *
     * @access public
     * @static
     * @throws Exception
     * @return bool
     */
    public static function canPurge()
    {
        // disable limits if set to less then 1
        if (self::$_limit < 1) {
            return true;
        }

        $file    = 'purge_limiter.php';
        $now     = time();
        $content = '<?php' . PHP_EOL . '$GLOBALS[\'purge_limiter\'] = ' . $now . ';' . PHP_EOL;
        if (!self::_exists($file)) {
            self::_store($file, $content);
        }

        $path = self::getPath($file);
        require $path;
        $pl = $GLOBALS['purge_limiter'];

        if ($pl + self::$_limit >= $now) {
            $result = false;
        } else {
            $result = true;
            self::_store($file, $content);
        }
        return $result;
    }
}
