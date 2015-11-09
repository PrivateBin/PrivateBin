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
 * model_paste
 *
 * Model of a ZeroBin paste.
 */
class model_paste extends model_abstract
{
    /**
     * Get paste data.
     *
     * @access public
     * @throws Exception
     * @return stdObject
     */
    public function get()
    {
        $this->_data = $this->_store->read($this->getId());
        // See if paste has expired and delete it if neccessary.
        if (property_exists($this->_data->meta, 'expire_date'))
        {
            if ($this->_data->meta->expire_date < time())
            {
                $this->delete();
                throw new Exception(zerobin::GENERIC_ERROR, 63);
            }
            // We kindly provide the remaining time before expiration (in seconds)
            $this->_data->meta->remaining_time = $this->_data->meta->expire_date - time();
        }

        // set formatter for for the view.
        if (!property_exists($this->_data->meta, 'formatter'))
        {
            // support < 0.21 syntax highlighting
            if (property_exists($this->_data->meta, 'syntaxcoloring') && $this->_data->meta->syntaxcoloring === true)
            {
                $this->_data->meta->formatter = 'syntaxhighlighting';
            }
            else
            {
                $this->_data->meta->formatter = $this->_conf->getKey('defaultformatter');
            }
        }
        $this->_data->comments = array_values($this->getComments());
        $this->_data->comment_count = count($this->_data->comments);
        $this->_data->comment_offset = 0;
        $this->_data->{'@context'} = 'js/paste.jsonld';
        return $this->_data;
    }

    /**
     * Store the paste's data.
     *
     * @access public
     * @throws Exception
     * @return void
     */
    public function store()
    {
        // Check for improbable collision.
        if ($this->exists())
            throw new Exception('You are unlucky. Try again.', 75);

        $this->_data->meta->postdate = time();

        // store paste
        if (
            $this->_store->create(
                $this->getId(),
                json_decode(json_encode($this->_data), true)
            ) === false
        ) throw new Exception('Error saving paste. Sorry.', 76);
    }

    /**
     * Delete the paste.
     *
     * @access public
     * @throws Exception
     * @return void
     */
    public function delete()
    {
        $this->_store->delete($this->getId());
    }

    /**
     * Test if paste exists in store.
     *
     * @access public
     * @return bool
     */
    public function exists()
    {
        return $this->_store->exists($this->getId());
    }

    /**
     * Get a comment, optionally a specific instance.
     *
     * @access public
     * @param string $parentId
     * @param string $commentId
     * @throws Exception
     * @return model_comment
     */
    public function getComment($parentId, $commentId = null)
    {
        if (!$this->exists())
        {
            throw new Exception('Invalid data.', 62);
        }
        $comment = new model_comment($this->_conf, $this->_store);
        $comment->setPaste($this);
        $comment->setParentId($parentId);
        if ($commentId !== null) $comment->setId($commentId);
        return $comment;
    }

    /**
     * Get all comments, if any.
     *
     * @access public
     * @return array
     */
    public function getComments()
    {
        return $this->_store->readComments($this->getId());
    }

    /**
     * Generate the "delete" token.
     *
     * The token is the hmac of the pastes ID signed with the server salt.
     * The paste can be deleted by calling:
     * http://example.com/zerobin/?pasteid=<pasteid>&deletetoken=<deletetoken>
     *
     * @access public
     * @return string
     */
    public function getDeleteToken()
    {
        return hash_hmac('sha1', $this->getId(), serversalt::get());
    }

    /**
     * Set paste's attachment.
     *
     * @access public
     * @param string $attachment
     * @throws Exception
     * @return void
     */
    public function setAttachment($attachment)
    {
        if (!$this->_conf->getKey('fileupload') || !sjcl::isValid($attachment))
            throw new Exception('Invalid attachment.', 71);
        $this->_data->meta->attachment = $attachment;
    }

    /**
     * Set paste's attachment name.
     *
     * @access public
     * @param string $attachmentname
     * @throws Exception
     * @return void
     */
    public function setAttachmentName($attachmentname)
    {
        if (!$this->_conf->getKey('fileupload') || !sjcl::isValid($attachmentname))
            throw new Exception('Invalid attachment.', 72);
        $this->_data->meta->attachmentname = $attachmentname;
    }

    /**
     * Set paste expiration.
     *
     * @access public
     * @param string $expiration
     * @return void
     */
    public function setExpiration($expiration)
    {
        $expire_options = $this->_conf->getSection('expire_options');
        if (array_key_exists($expiration, $expire_options))
        {
            $expire = $expire_options[$expiration];
        }
        else
        {
            // using getKey() to ensure a default value is present
            $expire = $this->_conf->getKey($this->_conf->getKey('default', 'expire'), 'expire_options');
        }
        if ($expire > 0) $this->_data->meta->expire_date = time() + $expire;
    }

    /**
     * Set paste's burn-after-reading type.
     *
     * @access public
     * @param string $burnafterreading
     * @throws Exception
     * @return void
     */
    public function setBurnafterreading($burnafterreading = '1')
    {
        if ($burnafterreading === '0')
        {
            $this->_data->meta->burnafterreading = false;
        }
        else
        {
            if ($burnafterreading !== '1')
                throw new Exception('Invalid data.', 73);
            $this->_data->meta->burnafterreading = true;
            $this->_data->meta->opendiscussion = false;
        }
    }

    /**
     * Set paste's discussion state.
     *
     * @access public
     * @param string $opendiscussion
     * @throws Exception
     * @return void
     */
    public function setOpendiscussion($opendiscussion = '1')
    {
        if (
            !$this->_conf->getKey('discussion') ||
            $this->isBurnafterreading() ||
            $opendiscussion === '0'
        )
        {
            $this->_data->meta->opendiscussion = false;
        }
        else
        {
            if ($opendiscussion !== '1')
                throw new Exception('Invalid data.', 74);
            $this->_data->meta->opendiscussion = true;
        }
    }

    /**
     * Set paste's format.
     *
     * @access public
     * @param string $format
     * @throws Exception
     * @return void
     */
    public function setFormatter($format)
    {
        if (!array_key_exists($format, $this->_conf->getSection('formatter_options')))
        {
            $format = $this->_conf->getKey('defaultformatter');
        }
        $this->_data->meta->formatter = $format;
    }

    /**
     * Check if paste is of burn-after-reading type.
     *
     * @access public
     * @throws Exception
     * @return boolean
     */
    public function isBurnafterreading()
    {
        if (!property_exists($this->_data, 'data')) $this->get();
        return property_exists($this->_data->meta, 'burnafterreading') &&
               $this->_data->meta->burnafterreading === true;
    }


    /**
     * Check if paste has discussions enabled.
     *
     * @access public
     * @throws Exception
     * @return boolean
     */
    public function isOpendiscussion()
    {
        if (!property_exists($this->_data, 'data')) $this->get();
        return property_exists($this->_data->meta, 'opendiscussion') &&
               $this->_data->meta->opendiscussion === true;
    }
}
