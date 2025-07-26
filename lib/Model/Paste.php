<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin\Model;

use Exception;
use PrivateBin\Controller;
use PrivateBin\Persistence\ServerSalt;

/**
 * Paste
 *
 * Model of a PrivateBin paste.
 */
class Paste extends AbstractModel
{
    /**
     * authenticated data index of paste formatter (plaintext/syntaxhighlighting/markdown)
     *
     * @const int
     */
    const ADATA_FORMATTER = 1;

    /**
     * authenticated data index of open-discussion flag (0/1)
     *
     * @const int
     */
    const ADATA_OPEN_DISCUSSION = 2;

    /**
     * authenticated data index of burn-after-reading flag (0/1)
     *
     * @const int
     */
    const ADATA_BURN_AFTER_READING = 3;

    /**
     * Get paste data.
     *
     * @access public
     * @throws Exception
     * @return array
     */
    public function get()
    {
        $data = $this->_store->read($this->getId());
        if ($data === false) {
            throw new Exception(Controller::GENERIC_ERROR, 64);
        }

        // check if paste has expired and delete it if necessary.
        if (array_key_exists('expire_date', $data['meta'])) {
            $now = time();
            if ($data['meta']['expire_date'] < $now) {
                $this->delete();
                throw new Exception(Controller::GENERIC_ERROR, 63);
            }
            // We kindly provide the remaining time before expiration (in seconds)
            $data['meta']['time_to_live'] = $data['meta']['expire_date'] - $now;
            unset($data['meta']['expire_date']);
        }
        if (array_key_exists('created', $data['meta'])) {
            unset($data['meta']['created']);
        }

        // check if non-expired burn after reading paste needs to be deleted
        if (
            array_key_exists('adata', $data) &&
            $data['adata'][self::ADATA_BURN_AFTER_READING] === 1
        ) {
            $this->delete();
        }

        $data['comments']       = array_values($this->getComments());
        $data['comment_count']  = count($data['comments']);
        $data['comment_offset'] = 0;
        $data['@context']       = '?jsonld=paste';
        $this->_data            = $data;

        return $this->_data;
    }

    /**
     * Store the paste's data.
     *
     * @access public
     * @throws Exception
     */
    public function store()
    {
        // Check for improbable collision.
        if ($this->exists()) {
            throw new Exception('You are unlucky. Try again.', 75);
        }

        $this->_data['meta']['salt'] = ServerSalt::generate();

        // store paste
        if (
            $this->_store->create(
                $this->getId(),
                $this->_data
            ) === false
        ) {
            throw new Exception('Error saving document. Sorry.', 76);
        }
    }

    /**
     * Delete the paste.
     *
     * @access public
     * @throws Exception
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
    public function getComment($parentId, $commentId = '')
    {
        if (!$this->exists()) {
            throw new Exception('Invalid data.', 62);
        }
        $comment = new Comment($this->_conf, $this->_store);
        $comment->setPaste($this);
        $comment->setParentId($parentId);
        if ($commentId !== '') {
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
        if ($this->_conf->getKey('discussiondatedisplay')) {
            return $this->_store->readComments($this->getId());
        }
        return array_map(function ($comment) {
            if (array_key_exists('created', $comment['meta'])) {
                unset($comment['meta']['created']);
            }
            return $comment;
        }, $this->_store->readComments($this->getId()));
    }

    /**
     * Generate the "delete" token.
     *
     * The token is the hmac of the pastes ID signed with the server salt.
     * The paste can be deleted by calling:
     * https://example.com/privatebin/?pasteid=<pasteid>&deletetoken=<deletetoken>
     *
     * @access public
     * @return string
     */
    public function getDeleteToken()
    {
        if (!array_key_exists('salt', $this->_data['meta'])) {
            $this->get();
        }
        return hash_hmac('sha256', $this->getId(), $this->_data['meta']['salt']);
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
        if (!array_key_exists('adata', $this->_data) && !array_key_exists('data', $this->_data)) {
            $this->get();
        }
        return array_key_exists('adata', $this->_data) &&
            $this->_data['adata'][self::ADATA_OPEN_DISCUSSION] === 1;
    }

    /**
     * Sanitizes data to conform with current configuration.
     *
     * @access protected
     * @param  array $data
     */
    protected function _sanitize(array &$data)
    {
        $expiration = $data['meta']['expire'] ?? 0;
        unset($data['meta']['expire']);
        $expire_options = $this->_conf->getSection('expire_options');
        if (array_key_exists($expiration, $expire_options)) {
            $expire = $expire_options[$expiration];
        } else {
            // using getKey() to ensure a default value is present
            $expire = $this->_conf->getKey($this->_conf->getKey('default', 'expire'), 'expire_options');
        }
        if ($expire > 0) {
            $data['meta']['expire_date'] = time() + $expire;
        }
    }

    /**
     * Validate data.
     *
     * @access protected
     * @param  array $data
     * @throws Exception
     */
    protected function _validate(array &$data)
    {
        // reject invalid or disabled formatters
        if (!array_key_exists($data['adata'][self::ADATA_FORMATTER], $this->_conf->getSection('formatter_options'))) {
            throw new Exception('Invalid data.', 75);
        }

        // discussion requested, but disabled in config or burn after reading requested as well, or invalid integer
        if (
            ($data['adata'][self::ADATA_OPEN_DISCUSSION] === 1 && (
                !$this->_conf->getKey('discussion') ||
                $data['adata'][self::ADATA_BURN_AFTER_READING] === 1
            )) ||
            ($data['adata'][self::ADATA_OPEN_DISCUSSION] !== 0 && $data['adata'][self::ADATA_OPEN_DISCUSSION] !== 1)
        ) {
            throw new Exception('Invalid data.', 74);
        }

        // reject invalid burn after reading
        if (
            $data['adata'][self::ADATA_BURN_AFTER_READING] !== 0 &&
            $data['adata'][self::ADATA_BURN_AFTER_READING] !== 1
        ) {
            throw new Exception('Invalid data.', 73);
        }
    }
}
