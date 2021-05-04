<?php

/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.3.5
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
     * listed ips are exempted from limits, defaults to null
     *
     * @access private
     * @static
     * @var    array
     */
    private static $_exemptedIp = null;

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
     * set a list of ip(ranges) as array
     *
     * @access public
     * @static
     * @param array $exemptedIps
     */
    public static function setExemptedIp($exemptedIp)
    {
        self::$_exemptedIp = $exemptedIp;
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
        self::setExemptedIp($conf->getKey('exemptedIp', 'traffic'));

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
    
        // Check if $_ipKey is exempted from ratelimiting
        if (!is_null(self::$_exemptedIp)) {
            $exIp_array = explode(",", self::$_exemptedIp);
            foreach ($exIp_array as $ipRange) {
                // Match $_ipKey to $ipRange and if it matches it will return with a true
                $address = \IPLib\Factory::addressFromString($_SERVER[self::$_ipKey]);
                $range = \IPLib\Factory::rangeFromString(trim($ipRange));
                // If $range is null something went wrong (possible invalid ip given in config)
                if ($range == null) {
                    $contained = false;
                } else {
                    // Ip-lib does throws and exception when something goes wrong, if so we want to catch it and set contained to false
                    try {
                        $contained = $address->matches($range);
                    } catch (Exception $e) {
                        // If something is wrong with matching the ip, we set $contained to false
                        $contained = false;
                    }
                }
                // Matches return true!
                if ($contained == true) {
                    return true;
                }
            }
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
