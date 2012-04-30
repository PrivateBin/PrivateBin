<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.15
 */

/**
 * traffic_limiter
 *
 * Handles traffic limiting, so no user does more than one call per 10 seconds.
 */
class traffic_limiter
{
    /**
     * @access private
     * @static
     * @var    int
     */
    private static $_limit = 10;

    /**
     * @access private
     * @static
     * @var    string
     */
    private static $_path = 'data';

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
     * set the path
     *
     * @access public
     * @static
     * @param  string $path
     * @return void
     */
    public static function setPath($path)
    {
        self::$_path = $path;
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
        // Create storage directory if it does not exist.
        if (!is_dir(self::$_path)) mkdir(self::$_path, 0705);
        // Create .htaccess file if it does not exist.
        if (!is_file(self::$_path . '/.htaccess'))
        {
            file_put_contents(
                self::$_path . '/.htaccess',
                'Allow from none' . PHP_EOL .
                'Deny from all'. PHP_EOL
            );
        }
        $file = self::$_path . '/traffic_limiter.php';
        if (!is_file($file))
        {
            file_put_contents(
                $file,
                '<?php' . PHP_EOL .
                '$GLOBALS[\'traffic_limiter\'] = array();' . PHP_EOL
            );
            chmod($file, 0705);
        }

        require $file;
        $tl = $GLOBALS['traffic_limiter'];

        // purge file of expired IPs to keep it small
        foreach($tl as $key => $time)
        {
            if ($time + 10 < time())
            {
                unset($tl[$key]);
            }
        }

        if (array_key_exists($ip, $tl) && ($tl[$ip] + 10 >= time()))
        {
            $result = false;
        } else {
            $tl[$ip] = time();
            $result = true;
        }
        file_put_contents(
            $file,
            '<?php' . PHP_EOL .
            '$GLOBALS[\'traffic_limiter\'] = ' .
            var_export($tl, true) . ';' . PHP_EOL
        );
        return $result;
    }
}
