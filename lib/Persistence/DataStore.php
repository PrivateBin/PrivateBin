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
use PrivateBin\Json;

/**
 * DataStore
 *
 * Handles data storage for Data\Filesystem.
 */
class DataStore extends AbstractPersistence
{
    /**
     * first line in file, to protect its contents
     *
     * @const string
     */
    const PROTECTION_LINE = '<?php http_response_code(403); /*';

    /**
     * store the data
     *
     * @access public
     * @static
     * @param  string $filename
     * @param  array  $data
     * @return bool
     */
    public static function store($filename, $data)
    {
        $path = self::getPath();
        if (strpos($filename, $path) === 0) {
            $filename = substr($filename, strlen($path));
        }
        try {
            self::_store(
                $filename,
                self::PROTECTION_LINE . PHP_EOL . Json::encode($data)
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * get the data
     *
     * @access public
     * @static
     * @param  string $filename
     * @return array|false $data
     */
    public static function get($filename)
    {
        return Json::decode(
            substr(
                file_get_contents($filename),
                strlen(self::PROTECTION_LINE . PHP_EOL)
            )
        );
    }

    /**
     * rename a file, prepending the protection line at the beginning
     *
     * @access public
     * @static
     * @param  string $srcFile
     * @param  string $destFile
     * @param  string $prefix (optional)
     * @return void
     */
    public static function prependRename($srcFile, $destFile, $prefix = '')
    {
        // don't overwrite already converted file
        if (!is_readable($destFile)) {
            $handle = fopen($srcFile, 'r', false, stream_context_create());
            file_put_contents($destFile, $prefix . self::PROTECTION_LINE . PHP_EOL);
            file_put_contents($destFile, $handle, FILE_APPEND);
            fclose($handle);
        }
        unlink($srcFile);
    }
}
