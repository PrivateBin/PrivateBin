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
 * persistence
 *
 * persists data in PHP files
 */
abstract class persistence
{
    /**
     * path in which to persist something
     *
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
        return is_file(self::$_path . DIRECTORY_SEPARATOR . $filename);
    }

    /**
     * prepares path for storage
     *
     * @access protected
     * @static
     * @throws Exception
     * @return void
     */
    protected static function _initialize()
    {
        // Create storage directory if it does not exist.
        if (!is_dir(self::$_path))
            if (!@mkdir(self::$_path))
                throw new Exception('unable to create directory ' . self::$_path, 10);

        // Create .htaccess file if it does not exist.
        $file = self::$_path . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($file))
        {
            $writtenBytes = @file_put_contents(
                $file,
                'Allow from none' . PHP_EOL .
                'Deny from all'. PHP_EOL,
                LOCK_EX
            );
            if ($writtenBytes === false || $writtenBytes < 30) {
                throw new Exception('unable to write to file ' . $file, 11);
            }
        }
    }

    /**
     * store the data
     *
     * @access protected
     * @static
     * @param  string $filename
     * @param  string $data
     * @throws Exception
     * @return string
     */
    protected static function _store($filename, $data)
    {
        self::_initialize();
        $file = self::$_path . DIRECTORY_SEPARATOR . $filename;
        $writtenBytes = @file_put_contents($file, $data, LOCK_EX);
        if ($writtenBytes === false || $writtenBytes < strlen($data)) {
            throw new Exception('unable to write to file ' . $file, 13);
        }
        return $file;
    }
}
