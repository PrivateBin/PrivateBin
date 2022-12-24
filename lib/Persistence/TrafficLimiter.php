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

use Exception;
use IPLib\Factory;
use IPLib\ParseStringFlag;
use PrivateBin\Configuration;
use PrivateBin\I18n;

/**
 * TrafficLimiter
 *
 * Handles traffic limiting, so no user does more than one call per 10 seconds.
 */
class TrafficLimiter extends AbstractPersistence
{
    /**
     * listed IPs are the only ones allowed to create, defaults to null
     *
     * @access private
     * @static
     * @var    string|null
     */
    private static $_creators = null;

    /**
     * listed IPs are exempted from limits, defaults to null
     *
     * @access private
     * @static
     * @var    string|null
     */
    private static $_exempted = null;

    /**
     * key to fetch IP address
     *
     * @access private
     * @static
     * @var    string
     */
    private static $_ipKey = 'REMOTE_ADDR';

    /**
     * time limit in seconds, defaults to 10s
     *
     * @access private
     * @static
     * @var    int
     */
    private static $_limit = 10;

    /**
     * set configuration options of the traffic limiter
     *
     * @access public
     * @static
     * @param Configuration $conf
     */
    public static function setConfiguration(Configuration $conf)
    {
        self::setCreators($conf->getKey('creators', 'traffic'));
        self::setExempted($conf->getKey('exempted', 'traffic'));
        self::setLimit($conf->getKey('limit', 'traffic'));

        if (($option = $conf->getKey('header', 'traffic')) !== '') {
            $httpHeader = 'HTTP_' . $option;
            if (array_key_exists($httpHeader, $_SERVER) && !empty($_SERVER[$httpHeader])) {
                self::$_ipKey = $httpHeader;
            }
        }
    }

    /**
     * set a list of creator IP(-ranges) as string
     *
     * @access public
     * @static
     * @param string $creators
     */
    public static function setCreators($creators)
    {
        self::$_creators = $creators;
    }

    /**
     * set a list of exempted IP(-ranges) as string
     *
     * @access public
     * @static
     * @param string $exempted
     */
    public static function setExempted($exempted)
    {
        self::$_exempted = $exempted;
    }

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
     * validate $_ipKey against configured ipranges. If matched we will ignore the ip
     *
     * @access private
     * @static
     * @param  string $ipRange
     * @return bool
     */
    private static function matchIp($ipRange = null)
    {
        if (is_string($ipRange)) {
            $ipRange = trim($ipRange);
        }
        $address = Factory::parseAddressString($_SERVER[self::$_ipKey]);
        $range   = Factory::parseRangeString(
            $ipRange,
            ParseStringFlag::IPV4_MAYBE_NON_DECIMAL | ParseStringFlag::IPV4SUBNET_MAYBE_COMPACT | ParseStringFlag::IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED
        );

        // address could not be parsed, we might not be in IP space and try a string comparison instead
        if (is_null($address)) {
            return $_SERVER[self::$_ipKey] === $ipRange;
        }
        // range could not be parsed, possibly an invalid ip range given in config
        if (is_null($range)) {
            return false;
        }

        return $address->matches($range);
    }

    /**
     * make sure the IP address is allowed to perfom a request
     *
     * @access public
     * @static
     * @throws Exception
     * @return true
     */
    public static function canPass()
    {
        // if creators are defined, the traffic limiter will only allow creation
        // for these, with no limits, and skip any other rules
        if (!empty(self::$_creators)) {
            $creatorIps = explode(',', self::$_creators);
            foreach ($creatorIps as $ipRange) {
                if (self::matchIp($ipRange) === true) {
                    return true;
                }
            }
            throw new Exception(I18n::_('Your IP is not authorized to create pastes.'));
        }

        // disable limits if set to less then 1
        if (self::$_limit < 1) {
            return true;
        }

        // check if $_ipKey is exempted from ratelimiting
        if (!empty(self::$_exempted)) {
            $exIp_array = explode(',', self::$_exempted);
            foreach ($exIp_array as $ipRange) {
                if (self::matchIp($ipRange) === true) {
                    return true;
                }
            }
        }

        // used as array key, which are limited in length, hence using algo with shorter range
        $hash = self::getHash('sha256');
        $now  = time();
        $tl   = (int) self::$_store->getValue('traffic_limiter', $hash);
        self::$_store->purgeValues('traffic_limiter', $now - self::$_limit);
        if ($tl > 0 && ($tl + self::$_limit >= $now)) {
            $result = false;
        } else {
            $tl     = time();
            $result = true;
        }
        if (!self::$_store->setValue((string) $tl, 'traffic_limiter', $hash)) {
            error_log('failed to store the traffic limiter, it probably contains outdated information');
        }
        if ($result) {
            return true;
        }
        throw new Exception(I18n::_(
            'Please wait %d seconds between each post.',
            self::$_limit
        ));
    }
}
