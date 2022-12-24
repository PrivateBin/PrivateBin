<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.5.1
 */

namespace PrivateBin\Data;

use Exception;
use PrivateBin\Json;

/**
 * Filesystem
 *
 * Model for filesystem data access, implemented as a singleton.
 */
class Filesystem extends AbstractData
{
    /**
     * glob() pattern of the two folder levels and the paste files under the
     * configured path. Needs to return both files with and without .php suffix,
     * so they can be hardened by _prependRename(), which is hooked into exists().
     *
     * > Note that wildcard patterns are not regular expressions, although they
     * > are a bit similar.
     *
     * @link  https://man7.org/linux/man-pages/man7/glob.7.html
     * @const string
     */
    const PASTE_FILE_PATTERN = DIRECTORY_SEPARATOR . '[a-f0-9][a-f0-9]' .
        DIRECTORY_SEPARATOR . '[a-f0-9][a-f0-9]' . DIRECTORY_SEPARATOR .
        '[a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9]' .
        '[a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9]*';

    /**
     * first line in paste or comment files, to protect their contents from browsing exposed data directories
     *
     * @const string
     */
    const PROTECTION_LINE = '<?php http_response_code(403); /*';

    /**
     * line in generated .htaccess files, to protect exposed directories from being browsable on apache web servers
     *
     * @const string
     */
    const HTACCESS_LINE = 'Require all denied';

    /**
     * path in which to persist something
     *
     * @access private
     * @var    string
     */
    private $_path = 'data';

    /**
     * instantiates a new Filesystem data backend
     *
     * @access public
     * @param  array $options
     * @return
     */
    public function __construct(array $options)
    {
        // if given update the data directory
        if (
            is_array($options) &&
            array_key_exists('dir', $options)
        ) {
            $this->_path = $options['dir'];
        }
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
        $storagedir = $this->_dataid2path($pasteid);
        $file       = $storagedir . $pasteid . '.php';
        if (is_file($file)) {
            return false;
        }
        if (!is_dir($storagedir)) {
            mkdir($storagedir, 0700, true);
        }
        return $this->_store($file, $paste);
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
        if (
            !$this->exists($pasteid) ||
            !$paste = $this->_get($this->_dataid2path($pasteid) . $pasteid . '.php')
        ) {
            return false;
        }
        return self::upgradePreV1Format($paste);
    }

    /**
     * Delete a paste and its discussion.
     *
     * @access public
     * @param  string $pasteid
     */
    public function delete($pasteid)
    {
        $pastedir = $this->_dataid2path($pasteid);
        if (is_dir($pastedir)) {
            // Delete the paste itself.
            if (is_file($pastedir . $pasteid . '.php')) {
                unlink($pastedir . $pasteid . '.php');
            }

            // Delete discussion if it exists.
            $discdir = $this->_dataid2discussionpath($pasteid);
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
        $basePath  = $this->_dataid2path($pasteid) . $pasteid;
        $pastePath = $basePath . '.php';
        // convert to PHP protected files if needed
        if (is_readable($basePath)) {
            $this->_prependRename($basePath, $pastePath);

            // convert comments, too
            $discdir = $this->_dataid2discussionpath($pasteid);
            if (is_dir($discdir)) {
                $dir = dir($discdir);
                while (false !== ($filename = $dir->read())) {
                    if (substr($filename, -4) !== '.php' && strlen($filename) >= 16) {
                        $commentFilename = $discdir . $filename . '.php';
                        $this->_prependRename($discdir . $filename, $commentFilename);
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
        $storagedir = $this->_dataid2discussionpath($pasteid);
        $file       = $storagedir . $pasteid . '.' . $commentid . '.' . $parentid . '.php';
        if (is_file($file)) {
            return false;
        }
        if (!is_dir($storagedir)) {
            mkdir($storagedir, 0700, true);
        }
        return $this->_store($file, $comment);
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
        $discdir  = $this->_dataid2discussionpath($pasteid);
        if (is_dir($discdir)) {
            $dir = dir($discdir);
            while (false !== ($filename = $dir->read())) {
                // Filename is in the form pasteid.commentid.parentid.php:
                // - pasteid is the paste this reply belongs to.
                // - commentid is the comment identifier itself.
                // - parentid is the comment this comment replies to (It can be pasteid)
                if (is_file($discdir . $filename)) {
                    $comment = $this->_get($discdir . $filename);
                    $items   = explode('.', $filename);
                    // Add some meta information not contained in file.
                    $comment['id']       = $items[1];
                    $comment['parentid'] = $items[2];

                    // Store in array
                    $key            = $this->getOpenSlot(
                        $comments, (
                            (int) array_key_exists('created', $comment['meta']) ?
                            $comment['meta']['created'] : // v2 comments
                            $comment['meta']['postdate'] // v1 comments
                        )
                    );
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
            $this->_dataid2discussionpath($pasteid) .
            $pasteid . '.' . $commentid . '.' . $parentid . '.php'
        );
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
    public function setValue($value, $namespace, $key = '')
    {
        switch ($namespace) {
            case 'purge_limiter':
                return $this->_storeString(
                    $this->_path . DIRECTORY_SEPARATOR . 'purge_limiter.php',
                    '<?php' . PHP_EOL . '$GLOBALS[\'purge_limiter\'] = ' . $value . ';'
                );
            case 'salt':
                return $this->_storeString(
                    $this->_path . DIRECTORY_SEPARATOR . 'salt.php',
                    '<?php # |' . $value . '|'
                );
            case 'traffic_limiter':
                $this->_last_cache[$key] = $value;
                return $this->_storeString(
                    $this->_path . DIRECTORY_SEPARATOR . 'traffic_limiter.php',
                    '<?php' . PHP_EOL . '$GLOBALS[\'traffic_limiter\'] = ' . var_export($this->_last_cache, true) . ';'
                );
        }
        return false;
    }

    /**
     * Load a value.
     *
     * @access public
     * @param  string $namespace
     * @param  string $key
     * @return string
     */
    public function getValue($namespace, $key = '')
    {
        switch ($namespace) {
            case 'purge_limiter':
                $file = $this->_path . DIRECTORY_SEPARATOR . 'purge_limiter.php';
                if (is_readable($file)) {
                    require $file;
                    return $GLOBALS['purge_limiter'];
                }
                break;
            case 'salt':
                $file = $this->_path . DIRECTORY_SEPARATOR . 'salt.php';
                if (is_readable($file)) {
                    $items = explode('|', file_get_contents($file));
                    if (is_array($items) && count($items) == 3) {
                        return $items[1];
                    }
                }
                break;
            case 'traffic_limiter':
                $file = $this->_path . DIRECTORY_SEPARATOR . 'traffic_limiter.php';
                if (is_readable($file)) {
                    require $file;
                    $this->_last_cache = $GLOBALS['traffic_limiter'];
                    if (array_key_exists($key, $this->_last_cache)) {
                        return $this->_last_cache[$key];
                    }
                }
                break;
        }
        return '';
    }

    /**
     * get the data
     *
     * @access public
     * @param  string $filename
     * @return array|false $data
     */
    private function _get($filename)
    {
        return Json::decode(
            substr(
                file_get_contents($filename),
                strlen(self::PROTECTION_LINE . PHP_EOL)
            )
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
        $pastes = array();
        $count  = 0;
        $opened = 0;
        $limit  = $batchsize * 10; // try at most 10 times $batchsize pastes before giving up
        $time   = time();
        $files  = $this->getAllPastes();
        shuffle($files);
        foreach ($files as $pasteid) {
            if ($this->exists($pasteid)) {
                $data = $this->read($pasteid);
                if (
                    array_key_exists('expire_date', $data['meta']) &&
                    $data['meta']['expire_date'] < $time
                ) {
                    $pastes[] = $pasteid;
                    if (++$count >= $batchsize) {
                        break;
                    }
                }
                if (++$opened >= $limit) {
                    break;
                }
            }
        }
        return $pastes;
    }

    /**
     * @inheritDoc
     */
    public function getAllPastes()
    {
        $pastes = array();
        foreach (new \GlobIterator($this->_path . self::PASTE_FILE_PATTERN) as $file) {
            if ($file->isFile()) {
                $pastes[] = $file->getBasename('.php');
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
     * @param  string $dataid
     * @return string
     */
    private function _dataid2path($dataid)
    {
        return $this->_path . DIRECTORY_SEPARATOR .
            substr($dataid, 0, 2) . DIRECTORY_SEPARATOR .
            substr($dataid, 2, 2) . DIRECTORY_SEPARATOR;
    }

    /**
     * Convert paste id to discussion storage path.
     *
     * eg. input 'e3570978f9e4aa90' --> output 'data/e3/57/e3570978f9e4aa90.discussion/'
     *
     * @access private
     * @param  string $dataid
     * @return string
     */
    private function _dataid2discussionpath($dataid)
    {
        return $this->_dataid2path($dataid) . $dataid .
            '.discussion' . DIRECTORY_SEPARATOR;
    }

    /**
     * store the data
     *
     * @access public
     * @param  string $filename
     * @param  array  $data
     * @return bool
     */
    private function _store($filename, array $data)
    {
        try {
            return $this->_storeString(
                $filename,
                self::PROTECTION_LINE . PHP_EOL . Json::encode($data)
            );
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * store a string
     *
     * @access public
     * @param  string $filename
     * @param  string $data
     * @return bool
     */
    private function _storeString($filename, $data)
    {
        // Create storage directory if it does not exist.
        if (!is_dir($this->_path)) {
            if (!@mkdir($this->_path, 0700)) {
                return false;
            }
        }
        $file = $this->_path . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($file)) {
            $writtenBytes = 0;
            if ($fileCreated = @touch($file)) {
                $writtenBytes = @file_put_contents(
                    $file,
                    self::HTACCESS_LINE . PHP_EOL,
                    LOCK_EX
                );
            }
            if (
                $fileCreated === false ||
                $writtenBytes === false ||
                $writtenBytes < strlen(self::HTACCESS_LINE . PHP_EOL)
            ) {
                return false;
            }
        }

        $fileCreated  = true;
        $writtenBytes = 0;
        if (!is_file($filename)) {
            $fileCreated = @touch($filename);
        }
        if ($fileCreated) {
            $writtenBytes = @file_put_contents($filename, $data, LOCK_EX);
        }
        if ($fileCreated === false || $writtenBytes === false || $writtenBytes < strlen($data)) {
            return false;
        }
        @chmod($filename, 0640); // protect file from access by other users on the host
        return true;
    }

    /**
     * rename a file, prepending the protection line at the beginning
     *
     * @access public
     * @param  string $srcFile
     * @param  string $destFile
     * @return void
     */
    private function _prependRename($srcFile, $destFile)
    {
        // don't overwrite already converted file
        if (!is_readable($destFile)) {
            $handle = fopen($srcFile, 'r', false, stream_context_create());
            file_put_contents($destFile, self::PROTECTION_LINE . PHP_EOL);
            file_put_contents($destFile, $handle, FILE_APPEND);
            fclose($handle);
        }
        unlink($srcFile);
    }
}
