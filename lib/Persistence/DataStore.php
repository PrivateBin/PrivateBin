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
     * First line in JSON files, to hide contents
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
            self::_store($filename, self::PROTECTION_LINE . PHP_EOL . Json::encode($data));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
