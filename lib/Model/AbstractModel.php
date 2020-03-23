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

namespace PrivateBin\Model;

use Exception;
use PrivateBin\Configuration;
use PrivateBin\Data\AbstractData;

/**
 * AbstractModel
 *
 * Abstract model for PrivateBin objects.
 */
abstract class AbstractModel
{
    /**
     * Instance ID.
     *
     * @access protected
     * @var string
     */
    protected $_id = '';

    /**
     * Instance data.
     *
     * @access protected
     * @var array
     */
    protected $_data = array('meta' => array());

    /**
     * Configuration.
     *
     * @access protected
     * @var Configuration
     */
    protected $_conf;

    /**
     * Data storage.
     *
     * @access protected
     * @var AbstractData
     */
    protected $_store;

    /**
     * Instance constructor.
     *
     * @access public
     * @param  Configuration $configuration
     * @param  AbstractData $storage
     */
    public function __construct(Configuration $configuration, AbstractData $storage)
    {
        $this->_conf       = $configuration;
        $this->_store      = $storage;
    }

    /**
     * Get ID.
     *
     * @access public
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set ID.
     *
     * @access public
     * @param string $id
     * @throws Exception
     */
    public function setId($id)
    {
        if (!self::isValidId($id)) {
            throw new Exception('Invalid paste ID.', 60);
        }
        $this->_id = $id;
    }

    /**
     * Set data and recalculate ID.
     *
     * @access public
     * @param  array $data
     * @throws Exception
     */
    public function setData(array $data)
    {
        $data = $this->_sanitize($data);
        $this->_validate($data);
        $this->_data = $data;

        // calculate a 64 bit checksum to avoid collisions
        $this->setId(hash(version_compare(PHP_VERSION, '5.6', '<') ? 'fnv164' : 'fnv1a64', $data['ct']));
    }

    /**
     * Get instance data.
     *
     * @access public
     * @return array
     */
    public function get()
    {
        return $this->_data;
    }

    /**
     * Store the instance's data.
     *
     * @access public
     * @throws Exception
     */
    abstract public function store();

    /**
     * Delete the current instance.
     *
     * @access public
     * @throws Exception
     */
    abstract public function delete();

    /**
     * Test if current instance exists in store.
     *
     * @access public
     * @return bool
     */
    abstract public function exists();

    /**
     * Validate ID.
     *
     * @access public
     * @static
     * @param  string $id
     * @return bool
     */
    public static function isValidId($id)
    {
        return (bool) preg_match('#\A[a-f\d]{16}\z#', (string) $id);
    }

    /**
     * Sanitizes data to conform with current configuration.
     *
     * @access protected
     * @param  array $data
     * @return array
     */
    abstract protected function _sanitize(array $data);

    /**
     * Validate data.
     *
     * @access protected
     * @param  array $data
     * @throws Exception
     */
    protected function _validate(array $data)
    {
    }
}
