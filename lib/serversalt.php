<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.18
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
        for($i=0; $i<16; ++$i) {
            $randomSalt .= base_convert(mt_rand(), 10, 16);
        }
        self::$_salt = $randomSalt;
        return self::$_salt;
    }

    /**
     * get server salt
     *
     * @access public
     * @static
     * @return string
     */
    public static function get()
    {
    	if (strlen(self::$_salt)) return self::$_salt;

        $file = 'salt.php';
        if (!self::_exists($file)) {
            self::_store(
            	$file,
                '<?php /* |'. self::generate() . '| */ ?>'
            );
        }
        $items = explode('|', file_get_contents(self::getPath($file)));
        self::$_salt = $items[1];
        return $items[1];
    }
}
