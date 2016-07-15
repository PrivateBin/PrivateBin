<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.22
 */

/**
 * model
 *
 * Factory of PrivateBin instance models.
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
     * @var privatebin_abstract
     */
    private $_store = null;

    /**
     * Factory constructor.
     *
     * @param configuration $conf
     * @return void
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
     * Checks if a purge is necessary and triggers it if yes.
     *
     * @return void
     */
    public function purge()
    {
        purgelimiter::setConfiguration($this->_conf);
        if (purgelimiter::canPurge())
        {
            $this->_getStore()->purge($this->_conf->getKey('batchsize', 'purge'));
        }
    }

    /**
     * Gets, and creates if neccessary, a store object
     *
     * @return privatebin_abstract
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
