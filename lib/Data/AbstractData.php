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

namespace PrivateBin\Data;

/**
 * AbstractData
 *
 * Abstract model for PrivateBin data access, implemented as a singleton.
 */
abstract class AbstractData
{
    /**
     * singleton instance
     *
     * @access protected
     * @static
     * @var AbstractData
     */
    protected static $_instance = null;

    /**
     * enforce singleton, disable constructor
     *
     * Instantiate using {@link getInstance()}, privatebin is a singleton object.
     *
     * @access protected
     */
    protected function __construct()
    {
    }

    /**
     * enforce singleton, disable cloning
     *
     * Instantiate using {@link getInstance()}, privatebin is a singleton object.
     *
     * @access private
     */
    private function __clone()
    {
    }

    /**
     * get instance of singleton
     *
     * @access public
     * @static
     * @param  array $options
     * @return AbstractData
     */
    public static function getInstance(array $options)
    {
    }

    /**
     * Create a paste.
     *
     * @access public
     * @param  string $pasteid
     * @param  array  $paste
     * @return bool
     */
    abstract public function create($pasteid, array $paste);

    /**
     * Read a paste.
     *
     * @access public
     * @param  string $pasteid
     * @return array|false
     */
    abstract public function read($pasteid);

    /**
     * Delete a paste and its discussion.
     *
     * @access public
     * @param  string $pasteid
     */
    abstract public function delete($pasteid);

    /**
     * Test if a paste exists.
     *
     * @access public
     * @param  string $pasteid
     * @return bool
     */
    abstract public function exists($pasteid);

    /**
     * Create a comment in a paste.
     *
     * @access public
     * @param  string $pasteid
     * @param  string $parentid
     * @param  string $commentid
     * @param  array  $comment
     * @return bool
     */
    abstract public function createComment($pasteid, $parentid, $commentid, array $comment);

    /**
     * Read all comments of paste.
     *
     * @access public
     * @param  string $pasteid
     * @return array
     */
    abstract public function readComments($pasteid);

    /**
     * Test if a comment exists.
     *
     * @access public
     * @param  string $pasteid
     * @param  string $parentid
     * @param  string $commentid
     * @return bool
     */
    abstract public function existsComment($pasteid, $parentid, $commentid);

    /**
     * Returns up to batch size number of paste ids that have expired
     *
     * @access protected
     * @param  int $batchsize
     * @return array
     */
    abstract protected function _getExpiredPastes($batchsize);

    /**
     * Perform a purge of old pastes, at most the given batchsize is deleted.
     *
     * @access public
     * @param  int $batchsize
     */
    public function purge($batchsize)
    {
        if ($batchsize < 1) {
            return;
        }
        $pastes = $this->_getExpiredPastes($batchsize);
        if (count($pastes)) {
            foreach ($pastes as $pasteid) {
                $this->delete($pasteid);
            }
        }
    }

    /**
     * Get next free slot for comment from postdate.
     *
     * @access protected
     * @param  array $comments
     * @param  int|string $postdate
     * @return int|string
     */
    protected function getOpenSlot(array &$comments, $postdate)
    {
        if (array_key_exists($postdate, $comments)) {
            $parts = explode('.', $postdate, 2);
            if (!array_key_exists(1, $parts)) {
                $parts[1] = 0;
            }
            ++$parts[1];
            return $this->getOpenSlot($comments, implode('.', $parts));
        }
        return $postdate;
    }

    /**
     * Upgrade pre-version 1 pastes with attachment to version 1 format.
     *
     * @access protected
     * @static
     * @param  array $paste
     * @return array
     */
    protected static function upgradePreV1Format(array $paste)
    {
        if (array_key_exists('attachment', $paste['meta'])) {
            $paste['attachment'] = $paste['meta']['attachment'];
            unset($paste['meta']['attachment']);
            if (array_key_exists('attachmentname', $paste['meta'])) {
                $paste['attachmentname'] = $paste['meta']['attachmentname'];
                unset($paste['meta']['attachmentname']);
            }
        }
        return $paste;
    }
}
