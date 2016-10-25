<?php
/**
 * PrivateBin.
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 *
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 *
 * @version   1.0
 */
namespace PrivateBin\Model;

use Exception;
use PrivateBin\Configuration;
use PrivateBin\Data\AbstractData;
use PrivateBin\Sjcl;
use stdClass;

/**
 * AbstractModel.
 *
 * Abstract model for PrivateBin objects.
 */
abstract class AbstractModel
{
    /**
     * Instance ID.
     *
     * @var string
     */
    protected $_id = '';

    /**
     * Instance data.
     *
     * @var stdClass
     */
    protected $_data;

    /**
     * Configuration.
     *
     * @var Configuration
     */
    protected $_conf;

    /**
     * Data storage.
     *
     * @var AbstractData
     */
    protected $_store;

    /**
     * Instance constructor.
     *
     * @param Configuration $configuration
     * @param AbstractData  $storage
     *
     * @return void
     */
    public function __construct(Configuration $configuration, AbstractData $storage)
    {
        $this->_conf = $configuration;
        $this->_store = $storage;
        $this->_data = new stdClass();
        $this->_data->meta = new stdClass();
    }

    /**
     * Get ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set ID.
     *
     * @param string $id
     *
     * @throws Exception
     *
     * @return void
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
     * @param string $data
     *
     * @throws Exception
     *
     * @return void
     */
    public function setData($data)
    {
        if (!Sjcl::isValid($data)) {
            throw new Exception('Invalid data.', 61);
        }
        $this->_data->data = $data;

        // We just want a small hash to avoid collisions:
        // Half-MD5 (64 bits) will do the trick
        $this->setId(substr(hash('md5', $data), 0, 16));
    }

    /**
     * Get instance data.
     *
     * @return stdClass
     */
    abstract public function get();

    /**
     * Store the instance's data.
     *
     * @throws Exception
     *
     * @return void
     */
    abstract public function store();

    /**
     * Delete the current instance.
     *
     * @throws Exception
     *
     * @return void
     */
    abstract public function delete();

    /**
     * Test if current instance exists in store.
     *
     * @return bool
     */
    abstract public function exists();

    /**
     * Validate ID.
     *
     * @static
     *
     * @param string $id
     *
     * @return bool
     */
    public static function isValidId($id)
    {
        return (bool) preg_match('#\A[a-f\d]{16}\z#', (string) $id);
    }
}
