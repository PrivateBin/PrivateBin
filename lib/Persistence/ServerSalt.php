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

use Exception;
use PrivateBin\Data\AbstractData;

/**
 * ServerSalt
 *
 * This is a random string which is unique to each PrivateBin installation.
 * It is automatically created if not present.
 *
 * Salt is used:
 * - to generate unique VizHash in discussions (which are not reproductible across PrivateBin servers)
 * - to generate unique deletion token (which are not re-usable across PrivateBin servers)
 */
class ServerSalt extends AbstractPersistence
{
    /**
     * file where salt is saved to
     *
     * @access private
     * @static
     * @var    string
     */
    private static $_file = 'salt.php';

    /**
     * generated salt
     *
     * @access private
     * @static
     * @var    string
     */
    private static $_salt = '';

    /**
     * generate a large random hexadecimal salt
     *
     * @access public
     * @static
     * @return string
     */
    public static function generate()
    {
        $randomSalt = bin2hex(random_bytes(256));
        return $randomSalt;
    }

    /**
     * get server salt
     *
     * @access public
     * @static
     * @throws Exception
     * @return string
     */
    public static function get()
    {
        if (strlen(self::$_salt)) {
            return self::$_salt;
        }

        $salt = self::$_store->getValue('salt');
        if ($salt) {
            self::$_salt = $salt;
        } else {
            self::$_salt = self::generate();
            self::$_store->setValue(self::$_salt, 'salt');
        }
        return self::$_salt;
    }

    /**
     * set the path
     *
     * @access public
     * @static
     * @param  AbstractData $store
     */
    public static function setStore(AbstractData $store)
    {
        self::$_salt = '';
        parent::setStore($store);
    }
}
