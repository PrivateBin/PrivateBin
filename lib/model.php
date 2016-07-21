<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.22
 */

namespace PrivateBin;

use PrivateBin\model\paste;

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
        $paste = new paste($this->_conf, $this->_getStore());
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
        // FIXME
        // Workaround so that config value don't need to be changed
        $callable = str_replace(
            array('privatebin_data', 'privatebin_db'),
            array('PrivateBin\\data\\data', 'PrivateBin\\data\\db'),
            $this->_conf->getKey('class', 'model')
        );

        if ($this->_store === null)
        {
            $this->_store = forward_static_call(
                array($callable, 'getInstance'),
                $this->_conf->getSection('model_options')
            );
        }
        return $this->_store;
    }
}
