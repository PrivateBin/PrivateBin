<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.5.1
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
    }

    /**
     * check if the purge can be performed
     *
     * @access public
     * @static
     * @return bool
     */
    public static function canPurge()
    {
        // disable limits if set to less then 1
        if (self::$_limit < 1) {
            return true;
        }

        $now  = time();
        $pl   = (int) self::$_store->getValue('purge_limiter');
        if ($pl + self::$_limit >= $now) {
            return false;
        }
        $hasStored = self::$_store->setValue((string) $now, 'purge_limiter');
        if (!$hasStored) {
            error_log('failed to store the purge limiter, skipping purge cycle to avoid getting stuck in a purge loop');
        }
        return $hasStored;
    }
}
