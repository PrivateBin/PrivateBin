<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.22
 */

/**
 * serversalt
 *
 * This is a random string which is unique to each ZeroBin installation.
 * It is automatically created if not present.
 *
 * Salt is used:
 * - to generate unique VizHash in discussions (which are not reproductible across ZeroBin servers)
 * - to generate unique deletion token (which are not re-usable across ZeroBin servers)
 */
class serversalt extends persistence
{
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
        $randomSalt = '';
        if (function_exists('mcrypt_create_iv'))
        {
            $randomSalt = bin2hex(mcrypt_create_iv(256, MCRYPT_DEV_URANDOM));
        }
        else // fallback to mt_rand()
        {
            for($i = 0; $i < 256; ++$i) {
                $randomSalt .= base_convert(mt_rand(), 10, 16);
            }
        }
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
        if (strlen(self::$_salt)) return self::$_salt;

        $file = 'salt.php';
        if (self::_exists($file)) {
            $items = explode('|', @file_get_contents(self::getPath($file)));
            if (!is_array($items) || count($items) != 3) {
                throw new Exception('unable to read file ' . self::getPath($file), 20);
            }
            self::$_salt = $items[1];
        } else {
            self::$_salt = self::generate();
            self::_store(
                $file,
                '<?php /* |'. self::$_salt . '| */ ?>'
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
     * @return void
     */
    public static function setPath($path)
    {
    	self::$_salt = '';
        parent::setPath($path);
    }
}
