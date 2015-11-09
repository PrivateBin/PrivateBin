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
 * model_comment
 *
 * Model of a ZeroBin comment.
 */
class model_comment extends model_abstract
{
    /**
     * Instance's parent.
     *
     * @access private
     * @var model_paste
     */
    private $_paste;

    /**
     * Get comment data.
     *
     * @access public
     * @throws Exception
     * @return stdObject
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
        if (!$this->getPaste()->exists())
            throw new Exception('Invalid data.', 67);

        // Make sure the discussion is opened in this paste and in configuration.
        if (!$this->getPaste()->isOpendiscussion() || !$this->_conf->getKey('discussion'))
            throw new Exception('Invalid data.', 68);

        // Check for improbable collision.
        if ($this->exists())
            throw new Exception('You are unlucky. Try again.', 69);

        $this->_data->meta->postdate = time();

        // store comment
        if (
            $this->_store->createComment(
                $pasteid,
                $this->getParentId(),
                $this->getId(),
                json_decode(json_encode($this->_data), true)
            ) === false
        ) throw new Exception('Error saving comment. Sorry.', 70);
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
     * @param model_paste $paste
     * @throws Exception
     * @return void
     */
    public function setPaste(model_paste $paste)
    {
        $this->_paste = $paste;
        $this->_data->meta->pasteid = $paste->getId();
    }

    /**
     * Get paste.
     *
     * @access public
     * @return model_paste
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
        if (!self::isValidId($id)) throw new Exception('Invalid paste ID.', 65);
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
        if (!property_exists($this->_data->meta, 'parentid')) $this->_data->meta->parentid = '';
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
        if (!sjcl::isValid($nickname)) throw new Exception('Invalid data.', 66);
        $this->_data->meta->nickname = $nickname;

        // Generation of the anonymous avatar (Vizhash):
        // If a nickname is provided, we generate a Vizhash.
        // (We assume that if the user did not enter a nickname, he/she wants
        // to be anonymous and we will not generate the vizhash.)
        $vh = new vizhash16x16();
        $pngdata = $vh->generate(trafficlimiter::getIp());
        if ($pngdata != '')
        {
            $this->_data->meta->vizhash = 'data:image/png;base64,' . base64_encode($pngdata);
        }
        // Once the avatar is generated, we do not keep the IP address, nor its hash.
    }
}
