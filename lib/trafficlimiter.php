<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.17
 */

/**
 * trafficlimiter
 *
 * Handles traffic limiting, so no user does more than one call per 10 seconds.
 */
class trafficlimiter extends persistence
{
    /**
     * @access private
     * @static
     * @var    int
     */
    private static $_limit = 10;

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
     * traffic limiter
     *
     * Make sure the IP address makes at most 1 request every 10 seconds.
     *
     * @access public
     * @static
     * @param  string $ip
     * @return bool
     */
    public static function canPass($ip)
    {
    	// disable limits if set to less then 1
    	if (self::$_limit < 1) return true;

        $file = 'traffic_limiter.php';
        if (!self::_exists($file))
        {
            self::_store(
                $file,
                '<?php' . PHP_EOL .
                '$GLOBALS[\'traffic_limiter\'] = array();' . PHP_EOL
            );
        }

        $path = self::getPath($file);
        require $path;
        $now = time();
        $tl = $GLOBALS['traffic_limiter'];

        // purge file of expired IPs to keep it small
        foreach($tl as $key => $time)
        {
            if ($time + self::$_limit < $now)
            {
                unset($tl[$key]);
            }
        }

        if (array_key_exists($ip, $tl) && ($tl[$ip] + self::$_limit >= $now))
        {
            $result = false;
        } else {
            $tl[$ip] = time();
            $result = true;
        }
        self::_store(
            $file,
            '<?php' . PHP_EOL .
            '$GLOBALS[\'traffic_limiter\'] = ' .
            var_export($tl, true) . ';' . PHP_EOL
        );
        return $result;
    }
}
