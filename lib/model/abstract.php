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
 * model_abstract
 *
 * Abstract model for ZeroBin objects.
 */
abstract class model_abstract
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
     * @var stdClass
     */
    protected $_data;

    /**
     * Configuration.
     *
     * @access protected
     * @var configuration
     */
    protected $_conf;

    /**
     * Data storage.
     *
     * @access protected
     * @var zerobin_abstract
     */
    protected $_store;

    /**
     * Instance constructor.
     *
     * @access public
     * @param  configuration $configuration
     * @param  zerobin_abstract $storage
     * @return void
     */
    public function __construct(configuration $configuration, zerobin_abstract $storage)
    {
        $this->_conf = $configuration;
        $this->_store = $storage;
        $this->_data = new stdClass;
        $this->_data->meta = new stdClass;
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
     * @return void
     */
    public function setId($id)
    {
        if (!self::isValidId($id)) throw new Exception('Invalid paste ID.', 60);
        $this->_id = $id;
    }

    /**
     * Set data and recalculate ID.
     *
     * @access public
     * @param string $data
     * @throws Exception
     * @return void
     */
    public function setData($data)
    {
        if (!sjcl::isValid($data)) throw new Exception('Invalid data.', 61);
        $this->_data->data = $data;

        // We just want a small hash to avoid collisions:
        // Half-MD5 (64 bits) will do the trick
        $this->setId(substr(hash('md5', $data), 0, 16));
    }

    /**
     * Get instance data.
     *
     * @access public
     * @return stdObject
     */
    abstract public function get();

    /**
     * Store the instance's data.
     *
     * @access public
     * @throws Exception
     * @return void
     */
    abstract public function store();

    /**
     * Delete the current instance.
     *
     * @access public
     * @throws Exception
     * @return void
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
}
