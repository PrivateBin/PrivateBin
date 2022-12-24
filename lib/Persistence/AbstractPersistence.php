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

use PrivateBin\Data\AbstractData;

/**
 * AbstractPersistence
 *
 * persists data in PHP files
 */
abstract class AbstractPersistence
{
    /**
     * data storage to use to persist something
     *
     * @access private
     * @static
     * @var AbstractData
     */
    protected static $_store;

    /**
     * set the path
     *
     * @access public
     * @static
     * @param  AbstractData $store
     */
    public static function setStore(AbstractData $store)
    {
        self::$_store = $store;
    }
}
