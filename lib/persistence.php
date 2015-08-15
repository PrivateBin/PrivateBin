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
 * persistence
 *
 * persists data in PHP files
 */
abstract class persistence
{
    /**
     * @access private
     * @static
     * @var    string
     */
    private static $_path = 'data';

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
     * get the path
     *
     * @access public
     * @static
     * @param  string $filename
     * @return void
     */
    public static function getPath($filename = null)
    {
        if(strlen($filename)) {
            return self::$_path . DIRECTORY_SEPARATOR . $filename;
        } else {
            return self::$_path;
        }
    }

    /**
     * checks if the file exists
     *
     * @access protected
     * @static
     * @param  string $filename
     * @return bool
     */
    protected static function _exists($filename)
    {
        self::_initialize();
        return is_file(self::$_path . '/' . $filename);
    }

    /**
     * prepares path for storage
     *
     * @access protected
     * @static
     * @return void
     */
    protected static function _initialize()
    {
        // Create storage directory if it does not exist.
        if (!is_dir(self::$_path)) mkdir(self::$_path, 0705);

        // Create .htaccess file if it does not exist.
        $file = self::$_path . '/.htaccess';
        if (!is_file($file))
        {
            file_put_contents(
                $file,
                'Allow from none' . PHP_EOL .
                'Deny from all'. PHP_EOL
            );
        }
    }

    /**
     * store the data
     *
     * @access protected
     * @static
     * @param  string $filename
     * @param  string $data
     * @return string
     */
    protected static function _store($filename, $data)
    {
        self::_initialize();
        $file = self::$_path . '/' . $filename;
        file_put_contents($file, $data);
        chmod($file, 0705);
        return $file;
    }
}
