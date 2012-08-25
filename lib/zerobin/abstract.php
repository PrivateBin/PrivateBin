<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.15
 */

/**
 * zerobin_abstract
 *
 * Abstract model for ZeroBin data access, implemented as a singleton.
 */
abstract class zerobin_abstract
{
	/**
     * singleton instance
     *
     * @access private
     * @static
     * @var zerobin
     */
    protected static $_instance = null;

    /**
     * enforce singleton, disable constructor
     *
     * Instantiate using {@link getInstance()}, zerobin is a singleton object.
     *
     * @access protected
     */
    protected function __construct() {}

    /**
     * enforce singleton, disable cloning
     *
     * Instantiate using {@link getInstance()}, zerobin is a singleton object.
     *
     * @access private
     */
    private function __clone() {}

    /**
     * get instance of singleton
     *
     * @access public
     * @static
     * @return zerobin_abstract
     */
    public static function getInstance($options) {}

    /**
     * Create a paste.
     *
     * @access public
     * @param  string $pasteid
     * @param  array  $paste
     * @return int|false
     */
    abstract public function create($pasteid, $paste);

    /**
     * Read a paste.
     *
     * @access public
     * @param  string $pasteid
     * @return string
     */
    abstract public function read($pasteid);

    /**
     * Delete a paste and its discussion.
     *
     * @access public
     * @param  string $pasteid
     * @return void
     */
    abstract public function delete($pasteid);

    /**
     * Test if a paste exists.
     *
     * @access public
     * @param  string $dataid
     * @return void
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
     * @return int|false
     */
    abstract public function createComment($pasteid, $parentid, $commentid, $comment);

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
     * @param  string $dataid
     * @param  string $parentid
     * @param  string $commentid
     * @return void
     */
    abstract public function existsComment($pasteid, $parentid, $commentid);
}
