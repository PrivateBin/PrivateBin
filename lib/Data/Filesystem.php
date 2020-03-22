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

use PrivateBin\Persistence\DataStore;

/**
 * Filesystem
 *
 * Model for filesystem data access, implemented as a singleton.
 */
class Filesystem extends AbstractData
{
    /**
     * get instance of singleton
     *
     * @access public
     * @static
     * @param  array $options
     * @return Filesystem
     */
    public static function getInstance(array $options)
    {
        // if needed initialize the singleton
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }
        // if given update the data directory
        if (
            is_array($options) &&
            array_key_exists('dir', $options)
        ) {
            DataStore::setPath($options['dir']);
        }
        return self::$_instance;
    }

    /**
     * Create a paste.
     *
     * @access public
     * @param  string $pasteid
     * @param  array  $paste
     * @return bool
     */
    public function create($pasteid, array $paste)
    {
        $storagedir = self::_dataid2path($pasteid);
        $file       = $storagedir . $pasteid . '.php';
        if (is_file($file)) {
            return false;
        }
        if (!is_dir($storagedir)) {
            mkdir($storagedir, 0700, true);
        }
        return DataStore::store($file, $paste);
    }

    /**
     * Read a paste.
     *
     * @access public
     * @param  string $pasteid
     * @return array|false
     */
    public function read($pasteid)
    {
        if (!$this->exists($pasteid)) {
            return false;
        }
        return self::upgradePreV1Format(
            DataStore::get(self::_dataid2path($pasteid) . $pasteid . '.php')
        );
    }

    /**
     * Delete a paste and its discussion.
     *
     * @access public
     * @param  string $pasteid
     */
    public function delete($pasteid)
    {
        $pastedir = self::_dataid2path($pasteid);
        if (is_dir($pastedir)) {
            // Delete the paste itself.
            if (is_file($pastedir . $pasteid . '.php')) {
                unlink($pastedir . $pasteid . '.php');
            }

            // Delete discussion if it exists.
            $discdir = self::_dataid2discussionpath($pasteid);
            if (is_dir($discdir)) {
                // Delete all files in discussion directory
                $dir = dir($discdir);
                while (false !== ($filename = $dir->read())) {
                    if (is_file($discdir . $filename)) {
                        unlink($discdir . $filename);
                    }
                }
                $dir->close();
                rmdir($discdir);
            }
        }
    }

    /**
     * Test if a paste exists.
     *
     * @access public
     * @param  string $pasteid
     * @return bool
     */
    public function exists($pasteid)
    {
        $basePath  = self::_dataid2path($pasteid) . $pasteid;
        $pastePath = $basePath . '.php';
        // convert to PHP protected files if needed
        if (is_readable($basePath)) {
            DataStore::prependRename($basePath, $pastePath);

            // convert comments, too
            $discdir = self::_dataid2discussionpath($pasteid);
            if (is_dir($discdir)) {
                $dir = dir($discdir);
                while (false !== ($filename = $dir->read())) {
                    if (substr($filename, -4) !== '.php' && strlen($filename) >= 16) {
                        $commentFilename = $discdir . $filename . '.php';
                        DataStore::prependRename($discdir . $filename, $commentFilename);
                    }
                }
                $dir->close();
            }
        }
        return is_readable($pastePath);
    }

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
    public function createComment($pasteid, $parentid, $commentid, array $comment)
    {
        $storagedir = self::_dataid2discussionpath($pasteid);
        $file       = $storagedir . $pasteid . '.' . $commentid . '.' . $parentid . '.php';
        if (is_file($file)) {
            return false;
        }
        if (!is_dir($storagedir)) {
            mkdir($storagedir, 0700, true);
        }
        return DataStore::store($file, $comment);
    }

    /**
     * Read all comments of paste.
     *
     * @access public
     * @param  string $pasteid
     * @return array
     */
    public function readComments($pasteid)
    {
        $comments = array();
        $discdir  = self::_dataid2discussionpath($pasteid);
        if (is_dir($discdir)) {
            $dir = dir($discdir);
            while (false !== ($filename = $dir->read())) {
                // Filename is in the form pasteid.commentid.parentid.php:
                // - pasteid is the paste this reply belongs to.
                // - commentid is the comment identifier itself.
                // - parentid is the comment this comment replies to (It can be pasteid)
                if (is_file($discdir . $filename)) {
                    $comment = DataStore::get($discdir . $filename);
                    $items   = explode('.', $filename);
                    // Add some meta information not contained in file.
                    $comment['id']       = $items[1];
                    $comment['parentid'] = $items[2];

                    // Store in array
                    $key            = $this->getOpenSlot($comments, (int) $comment['meta']['created']);
                    $comments[$key] = $comment;
                }
            }
            $dir->close();

            // Sort comments by date, oldest first.
            ksort($comments);
        }
        return $comments;
    }

    /**
     * Test if a comment exists.
     *
     * @access public
     * @param  string $pasteid
     * @param  string $parentid
     * @param  string $commentid
     * @return bool
     */
    public function existsComment($pasteid, $parentid, $commentid)
    {
        return is_file(
            self::_dataid2discussionpath($pasteid) .
            $pasteid . '.' . $commentid . '.' . $parentid . '.php'
        );
    }

    /**
     * Returns up to batch size number of paste ids that have expired
     *
     * @access private
     * @param  int $batchsize
     * @return array
     */
    protected function _getExpiredPastes($batchsize)
    {
        $pastes     = array();
        $mainpath   = DataStore::getPath();
        $firstLevel = array_filter(
            scandir($mainpath),
            'self::_isFirstLevelDir'
        );
        if (count($firstLevel) > 0) {
            // try at most 10 times the $batchsize pastes before giving up
            for ($i = 0, $max = $batchsize * 10; $i < $max; ++$i) {
                $firstKey    = array_rand($firstLevel);
                $secondLevel = array_filter(
                    scandir($mainpath . DIRECTORY_SEPARATOR . $firstLevel[$firstKey]),
                    'self::_isSecondLevelDir'
                );

                // skip this folder in the next checks if it is empty
                if (count($secondLevel) == 0) {
                    unset($firstLevel[$firstKey]);
                    continue;
                }

                $secondKey = array_rand($secondLevel);
                $path      = $mainpath . DIRECTORY_SEPARATOR .
                    $firstLevel[$firstKey] . DIRECTORY_SEPARATOR .
                    $secondLevel[$secondKey];
                if (!is_dir($path)) {
                    continue;
                }
                $thirdLevel = array_filter(
                    array_map(
                        function ($filename) {
                            return strlen($filename) >= 20 ?
                                substr($filename, 0, -4) :
                                $filename;
                        },
                        scandir($path)
                    ),
                    'PrivateBin\\Model\\Paste::isValidId'
                );
                if (count($thirdLevel) == 0) {
                    continue;
                }
                $thirdKey = array_rand($thirdLevel);
                $pasteid  = $thirdLevel[$thirdKey];
                if (in_array($pasteid, $pastes)) {
                    continue;
                }

                if ($this->exists($pasteid)) {
                    $data = $this->read($pasteid);
                    if (
                        array_key_exists('expire_date', $data['meta']) &&
                        $data['meta']['expire_date'] < time()
                    ) {
                        $pastes[] = $pasteid;
                        if (count($pastes) >= $batchsize) {
                            break;
                        }
                    }
                }
            }
        }
        return $pastes;
    }

    /**
     * Convert paste id to storage path.
     *
     * The idea is to creates subdirectories in order to limit the number of files per directory.
     * (A high number of files in a single directory can slow things down.)
     * eg. "f468483c313401e8" will be stored in "data/f4/68/f468483c313401e8"
     * High-trafic websites may want to deepen the directory structure (like Squid does).
     *
     * eg. input 'e3570978f9e4aa90' --> output 'data/e3/57/'
     *
     * @access private
     * @static
     * @param  string $dataid
     * @return string
     */
    private static function _dataid2path($dataid)
    {
        return DataStore::getPath(
            substr($dataid, 0, 2) . DIRECTORY_SEPARATOR .
            substr($dataid, 2, 2) . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Convert paste id to discussion storage path.
     *
     * eg. input 'e3570978f9e4aa90' --> output 'data/e3/57/e3570978f9e4aa90.discussion/'
     *
     * @access private
     * @static
     * @param  string $dataid
     * @return string
     */
    private static function _dataid2discussionpath($dataid)
    {
        return self::_dataid2path($dataid) . $dataid .
            '.discussion' . DIRECTORY_SEPARATOR;
    }

    /**
     * Check that the given element is a valid first level directory.
     *
     * @access private
     * @static
     * @param  string $element
     * @return bool
     */
    private static function _isFirstLevelDir($element)
    {
        return self::_isSecondLevelDir($element) &&
            is_dir(DataStore::getPath($element));
    }

    /**
     * Check that the given element is a valid second level directory.
     *
     * @access private
     * @static
     * @param  string $element
     * @return bool
     */
    private static function _isSecondLevelDir($element)
    {
        return (bool) preg_match('/^[a-f0-9]{2}$/', $element);
    }
}
