<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.22
 */

/**
 * purgelimiter
 *
 * Handles purge limiting, so purging is not triggered to often.
 */
class purgelimiter extends persistence
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
     * @param configuration $conf
     * @return void
     */
    public static function setConfiguration(configuration $conf)
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
        if (self::$_limit < 1) return true;

        $file = 'purge_limiter.php';
        $now = time();
        if (!self::_exists($file))
        {
            self::_store(
                $file,
                '<?php' . PHP_EOL .
                '$GLOBALS[\'purge_limiter\'] = ' . $now . ';' . PHP_EOL
            );
        }

        $path = self::getPath($file);
        require $path;
        $pl = $GLOBALS['purge_limiter'];

        if ($pl + self::$_limit >= $now)
        {
            $result = false;
        }
        else
        {
            $result = true;
            self::_store(
                $file,
                '<?php' . PHP_EOL .
                '$GLOBALS[\'purge_limiter\'] = ' . $now . ';' . PHP_EOL
            );
        }
        return $result;
    }
}
