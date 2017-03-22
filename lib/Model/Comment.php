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

namespace PrivateBin\Model;

use Exception;
use Identicon\Identicon;
use PrivateBin\Persistence\TrafficLimiter;
use PrivateBin\Sjcl;
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
     * Get comment data.
     *
     * @access public
     * @throws Exception
     * @return stdClass
     */
    public function get()
    {
        // @todo add support to read specific comment
        $comments = $this->_store->readComments($this->getPaste()->getId());
        foreach ($comments as $comment) {
            if (
                $comment->parentid == $this->getParentId() &&
                $comment->id == $this->getId()
            ) {
                $this->_data = $comment;
                break;
            }
        }
        return $this->_data;
    }

    /**
     * Store the comment's data.
     *
     * @access public
     * @throws Exception
     * @return void
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

        $this->_data->meta->postdate = time();

        // store comment
        if (
            $this->_store->createComment(
                $pasteid,
                $this->getParentId(),
                $this->getId(),
                json_decode(json_encode($this->_data), true)
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
     * @return void
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
     * @return void
     */
    public function setPaste(Paste $paste)
    {
        $this->_paste               = $paste;
        $this->_data->meta->pasteid = $paste->getId();
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
     * @return void
     */
    public function setParentId($id)
    {
        if (!self::isValidId($id)) {
            throw new Exception('Invalid paste ID.', 65);
        }
        $this->_data->meta->parentid = $id;
    }

    /**
     * Get parent ID.
     *
     * @access public
     * @return string
     */
    public function getParentId()
    {
        if (!property_exists($this->_data->meta, 'parentid')) {
            $this->_data->meta->parentid = '';
        }
        return $this->_data->meta->parentid;
    }

    /**
     * Set nickname.
     *
     * @access public
     * @param string $nickname
     * @throws Exception
     * @return void
     */
    public function setNickname($nickname)
    {
        if (!Sjcl::isValid($nickname)) {
            throw new Exception('Invalid data.', 66);
        }
        $this->_data->meta->nickname = $nickname;

        // If a nickname is provided, we generate an icon based on a SHA512 HMAC
        // of the users IP. (We assume that if the user did not enter a nickname,
        // the user wants to be anonymous and we will not generate an icon.)
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
                $this->_data->meta->vizhash = $pngdata;
            }
        }
        // Once the icon is generated, we do not keep the IP address hash.
    }
}
