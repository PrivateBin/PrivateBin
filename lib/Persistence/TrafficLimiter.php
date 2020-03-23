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
 * TrafficLimiter
 *
 * Handles traffic limiting, so no user does more than one call per 10 seconds.
 */
class TrafficLimiter extends AbstractPersistence
{
    /**
     * time limit in seconds, defaults to 10s
     *
     * @access private
     * @static
     * @var    int
     */
    private static $_limit = 10;

    /**
     * key to fetch IP address
     *
     * @access private
     * @static
     * @var    string
     */
    private static $_ipKey = 'REMOTE_ADDR';

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
        self::setLimit($conf->getKey('limit', 'traffic'));
        self::setPath($conf->getKey('dir', 'traffic'));
        if (($option = $conf->getKey('header', 'traffic')) !== null) {
            $httpHeader = 'HTTP_' . $option;
            if (array_key_exists($httpHeader, $_SERVER) && !empty($_SERVER[$httpHeader])) {
                self::$_ipKey = $httpHeader;
            }
        }
    }

    /**
     * get a HMAC of the current visitors IP address
     *
     * @access public
     * @static
     * @param  string $algo
     * @return string
     */
    public static function getHash($algo = 'sha512')
    {
        return hash_hmac($algo, $_SERVER[self::$_ipKey], ServerSalt::get());
    }

    /**
     * traffic limiter
     *
     * Make sure the IP address makes at most 1 request every 10 seconds.
     *
     * @access public
     * @static
     * @throws \Exception
     * @return bool
     */
    public static function canPass()
    {
        // disable limits if set to less then 1
        if (self::$_limit < 1) {
            return true;
        }

        $file = 'traffic_limiter.php';
        if (self::_exists($file)) {
            require self::getPath($file);
            $tl = $GLOBALS['traffic_limiter'];
        } else {
            $tl = array();
        }

        // purge file of expired hashes to keep it small
        $now = time();
        foreach ($tl as $key => $time) {
            if ($time + self::$_limit < $now) {
                unset($tl[$key]);
            }
        }

        // this hash is used as an array key, hence a shorter algo is used
        $hash = self::getHash('sha256');
        if (array_key_exists($hash, $tl) && ($tl[$hash] + self::$_limit >= $now)) {
            $result = false;
        } else {
            $tl[$hash] = time();
            $result    = true;
        }
        self::_store(
            $file,
            '<?php' . PHP_EOL .
            '$GLOBALS[\'traffic_limiter\'] = ' . var_export($tl, true) . ';'
        );
        return $result;
    }
}
