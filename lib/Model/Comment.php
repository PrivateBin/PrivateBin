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
use Identicon\Identicon;
use PrivateBin\Persistence\TrafficLimiter;
use PrivateBin\Vizhash16x16;

/**
 * Comment
 *
 * Model of a PrivateBin comment.
 */
class Comment extends AbstractModel
{
    /**
     * Instance's parent.
     *
     * @access private
     * @var Paste
     */
    private $_paste;

    /**
     * Store the comment's data.
     *
     * @access public
     * @throws Exception
     */
    public function store()
    {
        // Make sure paste exists.
        $pasteid = $this->getPaste()->getId();
        if (!$this->getPaste()->exists()) {
            throw new Exception('Invalid data.', 67);
        }

        // Make sure the discussion is opened in this paste and in configuration.
        if (!$this->getPaste()->isOpendiscussion() || !$this->_conf->getKey('discussion')) {
            throw new Exception('Invalid data.', 68);
        }

        // Check for improbable collision.
        if ($this->exists()) {
            throw new Exception('You are unlucky. Try again.', 69);
        }

        $this->_data['meta']['created'] = time();

        // store comment
        if (
            $this->_store->createComment(
                $pasteid,
                $this->getParentId(),
                $this->getId(),
                $this->_data
            ) === false
        ) {
            throw new Exception('Error saving comment. Sorry.', 70);
        }
    }

    /**
     * Delete the comment.
     *
     * @access public
     * @throws Exception
     */
    public function delete()
    {
        throw new Exception('To delete a comment, delete its parent paste', 64);
    }

    /**
     * Test if comment exists in store.
     *
     * @access public
     * @return bool
     */
    public function exists()
    {
        return $this->_store->existsComment(
            $this->getPaste()->getId(),
            $this->getParentId(),
            $this->getId()
        );
    }

    /**
     * Set paste.
     *
     * @access public
     * @param Paste $paste
     * @throws Exception
     */
    public function setPaste(Paste $paste)
    {
        $this->_paste           = $paste;
        $this->_data['pasteid'] = $paste->getId();
    }

    /**
     * Get paste.
     *
     * @access public
     * @return Paste
     */
    public function getPaste()
    {
        return $this->_paste;
    }

    /**
     * Set parent ID.
     *
     * @access public
     * @param string $id
     * @throws Exception
     */
    public function setParentId($id)
    {
        if (!self::isValidId($id)) {
            throw new Exception('Invalid paste ID.', 65);
        }
        $this->_data['parentid'] = $id;
    }

    /**
     * Get parent ID.
     *
     * @access public
     * @return string
     */
    public function getParentId()
    {
        if (!array_key_exists('parentid', $this->_data)) {
            $this->_data['parentid'] = $this->getPaste()->getId();
        }
        return $this->_data['parentid'];
    }

    /**
     * Sanitizes data to conform with current configuration.
     *
     * @access protected
     * @param  array $data
     * @return array
     */
    protected function _sanitize(array $data)
    {
        // we generate an icon based on a SHA512 HMAC of the users IP, if configured
        $icon = $this->_conf->getKey('icon');
        if ($icon != 'none') {
            $pngdata = '';
            $hmac    = TrafficLimiter::getHash();
            if ($icon == 'identicon') {
                $identicon = new Identicon();
                $pngdata   = $identicon->getImageDataUri($hmac, 16);
            } elseif ($icon == 'vizhash') {
                $vh      = new Vizhash16x16();
                $pngdata = 'data:image/png;base64,' . base64_encode(
                    $vh->generate($hmac)
                );
            }
            if ($pngdata != '') {
                if (!array_key_exists('meta', $data)) {
                    $data['meta'] = array();
                }
                $data['meta']['icon'] = $pngdata;
            }
        }
        return $data;
    }
}
