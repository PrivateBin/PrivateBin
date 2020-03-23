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
     * @throws \Exception
     * @return bool
     */
    public static function canPurge()
    {
        // disable limits if set to less then 1
        if (self::$_limit < 1) {
            return true;
        }

        $now  = time();
        $file = 'purge_limiter.php';
        if (self::_exists($file)) {
            require self::getPath($file);
            $pl = $GLOBALS['purge_limiter'];
            if ($pl + self::$_limit >= $now) {
                return false;
            }
        }

        $content = '<?php' . PHP_EOL . '$GLOBALS[\'purge_limiter\'] = ' . $now . ';';
        self::_store($file, $content);
        return true;
    }
}
