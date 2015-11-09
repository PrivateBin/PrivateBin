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
 * model
 *
 * Factory of ZeroBin instance models.
 */
class model
{
    /**
     * Configuration.
     *
     * @var configuration
     */
    private $_conf;

    /**
     * Data storage.
     *
     * @var zerobin_abstract
     */
    private $_store = null;

    /**
     * Factory constructor.
     *
     * @param configuration $conf
     */
    public function __construct(configuration $conf)
    {
        $this->_conf = $conf;
    }

    /**
     * Get a paste, optionally a specific instance.
     *
     * @param string $pasteId
     * @return model_paste
     */
    public function getPaste($pasteId = null)
    {
        $paste = new model_paste($this->_conf, $this->_getStore());
        if ($pasteId !== null) $paste->setId($pasteId);
        return $paste;
    }

    /**
     * Gets, and creates if neccessary, a store object
     */
    private function _getStore()
    {
        if ($this->_store === null)
        {
            $this->_store = forward_static_call(
                array($this->_conf->getKey('class', 'model'), 'getInstance'),
                $this->_conf->getSection('model_options')
            );
        }
        return $this->_store;
    }
}