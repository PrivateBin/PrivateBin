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
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\PrivateBin;
use PrivateBin\Sjcl;

/**
 * Paste
 *
 * Model of a PrivateBin paste.
 */
class Paste extends AbstractModel
{
    /**
     * Get paste data.
     *
     * @access public
     * @throws Exception
     * @return stdClass
     */
    public function get()
    {
        $data = $this->_store->read($this->getId());
        if ($data === false) {
            throw new Exception(PrivateBin::GENERIC_ERROR, 64);
        }

        // check if paste has expired and delete it if neccessary.
        if (property_exists($data->meta, 'expire_date')) {
            if ($data->meta->expire_date < time()) {
                $this->delete();
                throw new Exception(PrivateBin::GENERIC_ERROR, 63);
            }
            // We kindly provide the remaining time before expiration (in seconds)
            $data->meta->remaining_time = $data->meta->expire_date - time();
        }

        // set formatter for for the view.
        if (!property_exists($data->meta, 'formatter')) {
            // support < 0.21 syntax highlighting
            if (property_exists($data->meta, 'syntaxcoloring') && $data->meta->syntaxcoloring === true) {
                $data->meta->formatter = 'syntaxhighlighting';
            } else {
                $data->meta->formatter = $this->_conf->getKey('defaultformatter');
            }
        }

        // support old paste format with server wide salt
        if (!property_exists($data->meta, 'salt')) {
            $data->meta->salt = ServerSalt::get();
        }
        $data->comments       = array_values($this->getComments());
        $data->comment_count  = count($data->comments);
        $data->comment_offset = 0;
        $data->{'@context'}   = 'js/paste.jsonld';
        $this->_data          = $data;
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
        if ($this->exists()) {
            throw new Exception('You are unlucky. Try again.', 75);
        }

        $this->_data->meta->postdate = time();
        $this->_data->meta->salt     = serversalt::generate();

        // store paste
        if (
            $this->_store->create(
                $this->getId(),
                json_decode(json_encode($this->_data), true)
            ) === false
        ) {
            throw new Exception('Error saving paste. Sorry.', 76);
        }
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
     * @return Comment
     */
    public function getComment($parentId, $commentId = null)
    {
        if (!$this->exists()) {
            throw new Exception('Invalid data.', 62);
        }
        $comment = new Comment($this->_conf, $this->_store);
        $comment->setPaste($this);
        $comment->setParentId($parentId);
        if ($commentId !== null) {
            $comment->setId($commentId);
        }
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
     * http://example.com/privatebin/?pasteid=<pasteid>&deletetoken=<deletetoken>
     *
     * @access public
     * @return string
     */
    public function getDeleteToken()
    {
        if (!property_exists($this->_data->meta, 'salt')) {
            $this->get();
        }
        return hash_hmac(
            $this->_conf->getKey('zerobincompatibility') ? 'sha1' : 'sha256',
            $this->getId(),
            $this->_data->meta->salt
        );
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
        if (!$this->_conf->getKey('fileupload') || !Sjcl::isValid($attachment)) {
            throw new Exception('Invalid attachment.', 71);
        }
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
        if (!$this->_conf->getKey('fileupload') || !Sjcl::isValid($attachmentname)) {
            throw new Exception('Invalid attachment.', 72);
        }
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
        if (array_key_exists($expiration, $expire_options)) {
            $expire = $expire_options[$expiration];
        } else {
            // using getKey() to ensure a default value is present
            $expire = $this->_conf->getKey($this->_conf->getKey('default', 'expire'), 'expire_options');
        }
        if ($expire > 0) {
            $this->_data->meta->expire_date = time() + $expire;
        }
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
        if ($burnafterreading === '0') {
            $this->_data->meta->burnafterreading = false;
        } else {
            if ($burnafterreading !== '1') {
                throw new Exception('Invalid data.', 73);
            }
            $this->_data->meta->burnafterreading = true;
            $this->_data->meta->opendiscussion   = false;
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
        ) {
            $this->_data->meta->opendiscussion = false;
        } else {
            if ($opendiscussion !== '1') {
                throw new Exception('Invalid data.', 74);
            }
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
        if (!array_key_exists($format, $this->_conf->getSection('formatter_options'))) {
            $format = $this->_conf->getKey('defaultformatter');
        }
        $this->_data->meta->formatter = $format;
    }

    /**
     * Check if paste is of burn-after-reading type.
     *
     * @access public
     * @throws Exception
     * @return bool
     */
    public function isBurnafterreading()
    {
        if (!property_exists($this->_data, 'data')) {
            $this->get();
        }
        return property_exists($this->_data->meta, 'burnafterreading') &&
               $this->_data->meta->burnafterreading === true;
    }

    /**
     * Check if paste has discussions enabled.
     *
     * @access public
     * @throws Exception
     * @return bool
     */
    public function isOpendiscussion()
    {
        if (!property_exists($this->_data, 'data')) {
            $this->get();
        }
        return property_exists($this->_data->meta, 'opendiscussion') &&
               $this->_data->meta->opendiscussion === true;
    }
}
