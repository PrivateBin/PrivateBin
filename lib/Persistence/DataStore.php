<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.1
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
     * store the data
     *
     * @access public
     * @static
     * @param  string $filename
     * @param  string $data
     * @return bool
     */
    public static function store($filename, $data)
    {
        $path = self::getPath();
        if (strpos($filename, $path) === 0) {
            $filename = substr($filename, strlen($path));
        }
        try {
            self::_store($filename, Json::encode($data));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
