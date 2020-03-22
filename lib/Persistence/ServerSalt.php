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

use Exception;

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

        if (self::_exists(self::$_file)) {
            if (is_readable(self::getPath(self::$_file))) {
                $items = explode('|', file_get_contents(self::getPath(self::$_file)));
            }
            if (!isset($items) || !is_array($items) || count($items) != 3) {
                throw new Exception('unable to read file ' . self::getPath(self::$_file), 20);
            }
            self::$_salt = $items[1];
        } else {
            self::$_salt = self::generate();
            self::_store(
                self::$_file,
                '<?php # |' . self::$_salt . '|'
            );
        }
        return self::$_salt;
    }

    /**
     * set the path
     *
     * @access public
     * @static
     * @param  string $path
     */
    public static function setPath($path)
    {
        self::$_salt = '';
        parent::setPath($path);
    }
}
