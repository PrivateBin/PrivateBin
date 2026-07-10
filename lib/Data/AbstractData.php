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

namespace PrivateBin\Data;

/**
 * AbstractData
 *
 * Abstract model for data access
 */
abstract class AbstractData
{
    /**
     * cache for the traffic limiter
     *
     * @access protected
     * @var    array
     */
    protected $_last_cache = array();

    /**
     * Create a paste.
     *
     * @access public
     * @param  string $pasteid
     * @param  array  $paste
     * @return bool
     */
    abstract public function create($pasteid, array &$paste);

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
    abstract public function createComment($pasteid, $parentid, $commentid, array &$comment);

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
     * Purge outdated entries.
     *
     * @access public
     * @param  string $namespace
     * @param  int $time
     * @return void
     */
    public function purgeValues($namespace, $time)
    {
        if ($namespace === 'traffic_limiter') {
            foreach ($this->_last_cache as $key => $last_submission) {
                if ($last_submission <= $time) {
                    unset($this->_last_cache[$key]);
                }
            }
        }
    }

    /**
     * Save a value.
     *
     * @access public
     * @param  string $value
     * @param  string $namespace
     * @param  string $key
     * @return bool
     */
    abstract public function setValue($value, $namespace, $key = '');

    /**
     * Load a value.
     *
     * @access public
     * @param  string $namespace
     * @param  string $key
     * @return string
     */
    abstract public function getValue($namespace, $key = '');

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
     * Returns all paste ids
     *
     * @access public
     * @return array
     */
    abstract public function getAllPastes();

    /**
     * Get next free slot for comment from the creation timestamp
     *
     * The creation timestamp is usually a unix timestamp in seconds, but if a
     * comment already exists at that timestamp, a number, separated by a dot is
     * appended to it and incremented, then the function recurses until a free
     * slot is found.
     *
     * @access protected
     * @param  array $comments
     * @param  int|string $created
     * @return int|string
     */
    protected function getOpenSlot(array &$comments, $created)
    {
        if (array_key_exists($created, $comments)) {
            $parts = explode('.', (string) $created, 2);
            if (!array_key_exists(1, $parts)) {
                $parts[1] = 0;
            }
            ++$parts[1];
            return $this->getOpenSlot($comments, implode('.', $parts));
        }
        return $created;
    }

    /**
     * Create a new user
     *
     * @access public
     * @param  string $username
     * @param  array  $userData
     * @return bool
     */
    public function createUser(string $username, array $userData): bool
    {
        return false;
    }

    /**
     * Read user data
     *
     * @access public
     * @param  string $username
     * @return array|null
     */
    public function readUser(string $username): ?array
    {
        return null;
    }

    /**
     * Update user data
     *
     * @access public
     * @param  string $username
     * @param  array  $userData
     * @return bool
     */
    public function updateUser(string $username, array $userData): bool
    {
        return false;
    }

    /**
     * Delete a user
     *
     * @access public
     * @param  string $username
     * @return bool
     */
    public function deleteUser(string $username): bool
    {
        return false;
    }

    /**
     * List all users
     *
     * @access public
     * @return array
     */
    public function listUsers(): array
    {
        return array();
    }

    /**
     * Check if any users exist
     *
     * @access public
     * @return bool
     */
    public function hasUsers(): bool
    {
        return false;
    }

    /**
     * Create a user session record
     *
     * @access public
     * @param  string $sessionId
     * @param  array  $sessionData
     * @return bool
     */
    public function createSession(string $sessionId, array $sessionData): bool
    {
        return false;
    }

    /**
     * Read a user session record
     *
     * @access public
     * @param  string $sessionId
     * @return array|null
     */
    public function readSession(string $sessionId): ?array
    {
        return null;
    }

    /**
     * Delete a user session
     *
     * @access public
     * @param  string $sessionId
     * @return bool
     */
    public function deleteSession(string $sessionId): bool
    {
        return false;
    }

    /**
     * Purge expired user sessions
     *
     * @access public
     * @return void
     */
    public function purgeExpiredSessions(): void
    {
    }
}
