<?php
/**
 * PrivateBin.
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 *
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 *
 * @version   1.0
 */
namespace PrivateBin\Data;

use stdClass;

/**
 * AbstractData.
 *
 * Abstract model for PrivateBin data access, implemented as a singleton.
 */
abstract class AbstractData
{
    /**
     * singleton instance.
     *
     * @static
     *
     * @var AbstractData
     */
    protected static $_instance = null;

    /**
     * enforce singleton, disable constructor.
     *
     * Instantiate using {@link getInstance()}, privatebin is a singleton object.
     */
    protected function __construct()
    {
    }

    /**
     * enforce singleton, disable cloning.
     *
     * Instantiate using {@link getInstance()}, privatebin is a singleton object.
     */
    private function __clone()
    {
    }

    /**
     * get instance of singleton.
     *
     * @static
     *
     * @param array $options
     *
     * @return privatebin_abstract
     */
    public static function getInstance($options)
    {
    }

    /**
     * Create a paste.
     *
     * @param string $pasteid
     * @param array  $paste
     *
     * @return bool
     */
    abstract public function create($pasteid, $paste);

    /**
     * Read a paste.
     *
     * @param string $pasteid
     *
     * @return stdClass|false
     */
    abstract public function read($pasteid);

    /**
     * Delete a paste and its discussion.
     *
     * @param string $pasteid
     *
     * @return void
     */
    abstract public function delete($pasteid);

    /**
     * Test if a paste exists.
     *
     * @param string $pasteid
     *
     * @return bool
     */
    abstract public function exists($pasteid);

    /**
     * Create a comment in a paste.
     *
     * @param string $pasteid
     * @param string $parentid
     * @param string $commentid
     * @param array  $comment
     *
     * @return bool
     */
    abstract public function createComment($pasteid, $parentid, $commentid, $comment);

    /**
     * Read all comments of paste.
     *
     * @param string $pasteid
     *
     * @return array
     */
    abstract public function readComments($pasteid);

    /**
     * Test if a comment exists.
     *
     * @param string $pasteid
     * @param string $parentid
     * @param string $commentid
     *
     * @return bool
     */
    abstract public function existsComment($pasteid, $parentid, $commentid);

    /**
     * Returns up to batch size number of paste ids that have expired.
     *
     * @param int $batchsize
     *
     * @return array
     */
    abstract protected function _getExpiredPastes($batchsize);

    /**
     * Perform a purge of old pastes, at most the given batchsize is deleted.
     *
     * @param int $batchsize
     *
     * @return void
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
     * @param array      $comments
     * @param int|string $postdate
     *
     * @return int|string
     */
    protected function getOpenSlot(&$comments, $postdate)
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
}
