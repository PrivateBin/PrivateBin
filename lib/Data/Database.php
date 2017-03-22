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

namespace PrivateBin\Data;

use Exception;
use PDO;
use PDOException;
use PrivateBin\PrivateBin;
use stdClass;

/**
 * Database
 *
 * Model for database access, implemented as a singleton.
 */
class Database extends AbstractData
{
    /**
     * cache for select queries
     *
     * @var array
     */
    private static $_cache = array();

    /**
     * instance of database connection
     *
     * @access private
     * @static
     * @var PDO
     */
    private static $_db;

    /**
     * table prefix
     *
     * @access private
     * @static
     * @var string
     */
    private static $_prefix = '';

    /**
     * database type
     *
     * @access private
     * @static
     * @var string
     */
    private static $_type = '';

    /**
     * get instance of singleton
     *
     * @access public
     * @static
     * @param  array $options
     * @throws Exception
     * @return Database
     */
    public static function getInstance($options = null)
    {
        // if needed initialize the singleton
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }

        if (is_array($options)) {
            // set table prefix if given
            if (array_key_exists('tbl', $options)) {
                self::$_prefix = $options['tbl'];
            }

            // initialize the db connection with new options
            if (
                array_key_exists('dsn', $options) &&
                array_key_exists('usr', $options) &&
                array_key_exists('pwd', $options) &&
                array_key_exists('opt', $options)
            ) {
                // set default options
                $options['opt'][PDO::ATTR_ERRMODE]          = PDO::ERRMODE_EXCEPTION;
                $options['opt'][PDO::ATTR_EMULATE_PREPARES] = false;
                $options['opt'][PDO::ATTR_PERSISTENT]       = true;
                $db_tables_exist                            = true;

                // setup type and dabase connection
                self::$_type = strtolower(
                    substr($options['dsn'], 0, strpos($options['dsn'], ':'))
                );
                $tableQuery = self::_getTableQuery(self::$_type);
                self::$_db  = new PDO(
                    $options['dsn'],
                    $options['usr'],
                    $options['pwd'],
                    $options['opt']
                );

                // check if the database contains the required tables
                $tables = self::$_db->query($tableQuery)->fetchAll(PDO::FETCH_COLUMN, 0);

                // create paste table if necessary
                if (!in_array(self::_sanitizeIdentifier('paste'), $tables)) {
                    self::_createPasteTable();
                    $db_tables_exist = false;
                }

                // create comment table if necessary
                if (!in_array(self::_sanitizeIdentifier('comment'), $tables)) {
                    self::_createCommentTable();
                    $db_tables_exist = false;
                }

                // create config table if necessary
                $db_version = PrivateBin::VERSION;
                if (!in_array(self::_sanitizeIdentifier('config'), $tables)) {
                    self::_createConfigTable();
                    // if we only needed to create the config table, the DB is older then 0.22
                    if ($db_tables_exist) {
                        $db_version = '0.21';
                    }
                } else {
                    $db_version = self::_getConfig('VERSION');
                }

                // update database structure if necessary
                if (version_compare($db_version, PrivateBin::VERSION, '<')) {
                    self::_upgradeDatabase($db_version);
                }
            } else {
                throw new Exception(
                    'Missing configuration for key dsn, usr, pwd or opt in the section model_options, please check your configuration file', 6
                );
            }
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
    public function create($pasteid, $paste)
    {
        if (
            array_key_exists($pasteid, self::$_cache)
        ) {
            if (false !== self::$_cache[$pasteid]) {
                return false;
            } else {
                unset(self::$_cache[$pasteid]);
            }
        }

        $opendiscussion = $burnafterreading = false;
        $attachment     = $attachmentname     = '';
        $meta           = $paste['meta'];
        unset($meta['postdate']);
        $expire_date = 0;
        if (array_key_exists('expire_date', $paste['meta'])) {
            $expire_date = (int) $paste['meta']['expire_date'];
            unset($meta['expire_date']);
        }
        if (array_key_exists('opendiscussion', $paste['meta'])) {
            $opendiscussion = (bool) $paste['meta']['opendiscussion'];
            unset($meta['opendiscussion']);
        }
        if (array_key_exists('burnafterreading', $paste['meta'])) {
            $burnafterreading = (bool) $paste['meta']['burnafterreading'];
            unset($meta['burnafterreading']);
        }
        if (array_key_exists('attachment', $paste['meta'])) {
            $attachment = $paste['meta']['attachment'];
            unset($meta['attachment']);
        }
        if (array_key_exists('attachmentname', $paste['meta'])) {
            $attachmentname = $paste['meta']['attachmentname'];
            unset($meta['attachmentname']);
        }
        return self::_exec(
            'INSERT INTO ' . self::_sanitizeIdentifier('paste') .
            ' VALUES(?,?,?,?,?,?,?,?,?)',
            array(
                $pasteid,
                $paste['data'],
                $paste['meta']['postdate'],
                $expire_date,
                (int) $opendiscussion,
                (int) $burnafterreading,
                json_encode($meta),
                $attachment,
                $attachmentname,
            )
        );
    }

    /**
     * Read a paste.
     *
     * @access public
     * @param  string $pasteid
     * @return stdClass|false
     */
    public function read($pasteid)
    {
        if (
            !array_key_exists($pasteid, self::$_cache)
        ) {
            self::$_cache[$pasteid] = false;
            $paste                  = self::_select(
                'SELECT * FROM ' . self::_sanitizeIdentifier('paste') .
                ' WHERE dataid = ?', array($pasteid), true
            );

            if (false !== $paste) {
                // create object
                self::$_cache[$pasteid]       = new stdClass;
                self::$_cache[$pasteid]->data = $paste['data'];

                $meta = json_decode($paste['meta']);
                if (!is_object($meta)) {
                    $meta = new stdClass;
                }

                // support older attachments
                if (property_exists($meta, 'attachment')) {
                    self::$_cache[$pasteid]->attachment = $meta->attachment;
                    unset($meta->attachment);
                    if (property_exists($meta, 'attachmentname')) {
                        self::$_cache[$pasteid]->attachmentname = $meta->attachmentname;
                        unset($meta->attachmentname);
                    }
                }
                // support current attachments
                elseif (array_key_exists('attachment', $paste) && strlen($paste['attachment'])) {
                    self::$_cache[$pasteid]->attachment = $paste['attachment'];
                    if (array_key_exists('attachmentname', $paste) && strlen($paste['attachmentname'])) {
                        self::$_cache[$pasteid]->attachmentname = $paste['attachmentname'];
                    }
                }
                self::$_cache[$pasteid]->meta           = $meta;
                self::$_cache[$pasteid]->meta->postdate = (int) $paste['postdate'];
                $expire_date                            = (int) $paste['expiredate'];
                if (
                    $expire_date > 0
                ) {
                    self::$_cache[$pasteid]->meta->expire_date = $expire_date;
                }
                if (
                    $paste['opendiscussion']
                ) {
                    self::$_cache[$pasteid]->meta->opendiscussion = true;
                }
                if (
                    $paste['burnafterreading']
                ) {
                    self::$_cache[$pasteid]->meta->burnafterreading = true;
                }
            }
        }

        return self::$_cache[$pasteid];
    }

    /**
     * Delete a paste and its discussion.
     *
     * @access public
     * @param  string $pasteid
     * @return void
     */
    public function delete($pasteid)
    {
        self::_exec(
            'DELETE FROM ' . self::_sanitizeIdentifier('paste') .
            ' WHERE dataid = ?', array($pasteid)
        );
        self::_exec(
            'DELETE FROM ' . self::_sanitizeIdentifier('comment') .
            ' WHERE pasteid = ?', array($pasteid)
        );
        if (
            array_key_exists($pasteid, self::$_cache)
        ) {
            unset(self::$_cache[$pasteid]);
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
        if (
            !array_key_exists($pasteid, self::$_cache)
        ) {
            self::$_cache[$pasteid] = $this->read($pasteid);
        }
        return (bool) self::$_cache[$pasteid];
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
    public function createComment($pasteid, $parentid, $commentid, $comment)
    {
        foreach (array('nickname', 'vizhash') as $key) {
            if (!array_key_exists($key, $comment['meta'])) {
                $comment['meta'][$key] = null;
            }
        }
        return self::_exec(
            'INSERT INTO ' . self::_sanitizeIdentifier('comment') .
            ' VALUES(?,?,?,?,?,?,?)',
            array(
                $commentid,
                $pasteid,
                $parentid,
                $comment['data'],
                $comment['meta']['nickname'],
                $comment['meta']['vizhash'],
                $comment['meta']['postdate'],
            )
        );
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
        $rows = self::_select(
            'SELECT * FROM ' . self::_sanitizeIdentifier('comment') .
            ' WHERE pasteid = ?', array($pasteid)
        );

        // create comment list
        $comments = array();
        if (count($rows)) {
            foreach ($rows as $row) {
                $i                            = $this->getOpenSlot($comments, (int) $row['postdate']);
                $comments[$i]                 = new stdClass;
                $comments[$i]->id             = $row['dataid'];
                $comments[$i]->parentid       = $row['parentid'];
                $comments[$i]->data           = $row['data'];
                $comments[$i]->meta           = new stdClass;
                $comments[$i]->meta->postdate = (int) $row['postdate'];
                if (array_key_exists('nickname', $row) && !empty($row['nickname'])) {
                    $comments[$i]->meta->nickname = $row['nickname'];
                }
                if (array_key_exists('vizhash', $row) && !empty($row['vizhash'])) {
                    $comments[$i]->meta->vizhash = $row['vizhash'];
                }
            }
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
        return (bool) self::_select(
            'SELECT dataid FROM ' . self::_sanitizeIdentifier('comment') .
            ' WHERE pasteid = ? AND parentid = ? AND dataid = ?',
            array($pasteid, $parentid, $commentid), true
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
        $rows   = self::_select(
            'SELECT dataid FROM ' . self::_sanitizeIdentifier('paste') .
            ' WHERE expiredate < ? AND expiredate != ? LIMIT ?', array(time(), 0, $batchsize)
        );
        if (count($rows)) {
            foreach ($rows as $row) {
                $pastes[] = $row['dataid'];
            }
        }
        return $pastes;
    }

    /**
     * execute a statement
     *
     * @access private
     * @static
     * @param  string $sql
     * @param  array $params
     * @throws PDOException
     * @return bool
     */
    private static function _exec($sql, array $params)
    {
        $statement = self::$_db->prepare($sql);
        $result    = $statement->execute($params);
        $statement->closeCursor();
        return $result;
    }

    /**
     * run a select statement
     *
     * @access private
     * @static
     * @param  string $sql
     * @param  array $params
     * @param  bool $firstOnly if only the first row should be returned
     * @throws PDOException
     * @return array
     */
    private static function _select($sql, array $params, $firstOnly = false)
    {
        $statement = self::$_db->prepare($sql);
        $statement->execute($params);
        $result = $firstOnly ?
            $statement->fetch(PDO::FETCH_ASSOC) :
            $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }

    /**
     * get table list query, depending on the database type
     *
     * @access private
     * @static
     * @param  string $type
     * @throws Exception
     * @return string
     */
    private static function _getTableQuery($type)
    {
        switch ($type) {
            case 'ibm':
                $sql = 'SELECT tabname FROM SYSCAT.TABLES ';
                break;
            case 'informix':
                $sql = 'SELECT tabname FROM systables ';
                break;
            case 'mssql':
                $sql = 'SELECT name FROM sysobjects '
                     . "WHERE type = 'U' ORDER BY name";
                break;
            case 'mysql':
                $sql = 'SHOW TABLES';
                break;
            case 'oci':
                $sql = 'SELECT table_name FROM all_tables';
                break;
            case 'pgsql':
                $sql = 'SELECT c.relname AS table_name '
                     . 'FROM pg_class c, pg_user u '
                     . "WHERE c.relowner = u.usesysid AND c.relkind = 'r' "
                     . 'AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname) '
                     . "AND c.relname !~ '^(pg_|sql_)' "
                     . 'UNION '
                     . 'SELECT c.relname AS table_name '
                     . 'FROM pg_class c '
                     . "WHERE c.relkind = 'r' "
                     . 'AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname) '
                     . 'AND NOT EXISTS (SELECT 1 FROM pg_user WHERE usesysid = c.relowner) '
                     . "AND c.relname !~ '^pg_'";
                break;
            case 'sqlite':
                $sql = "SELECT name FROM sqlite_master WHERE type='table' "
                     . 'UNION ALL SELECT name FROM sqlite_temp_master '
                     . "WHERE type='table' ORDER BY name";
                break;
            default:
                throw new Exception(
                    "PDO type $type is currently not supported.", 5
                );
        }
        return $sql;
    }

    /**
     * get a value by key from the config table
     *
     * @access private
     * @static
     * @param  string $key
     * @throws PDOException
     * @return string
     */
    private static function _getConfig($key)
    {
        $row = self::_select(
            'SELECT value FROM ' . self::_sanitizeIdentifier('config') .
            ' WHERE id = ?', array($key), true
        );
        return $row['value'];
    }

    /**
     * get the primary key clauses, depending on the database driver
     *
     * @access private
     * @static
     * @param string $key
     * @return array
     */
    private static function _getPrimaryKeyClauses($key = 'dataid')
    {
        $main_key = $after_key = '';
        if (self::$_type === 'mysql') {
            $after_key = ", PRIMARY KEY ($key)";
        } else {
            $main_key = ' PRIMARY KEY';
        }
        return array($main_key, $after_key);
    }

    /**
     * create the paste table
     *
     * @access private
     * @static
     * @return void
     */
    private static function _createPasteTable()
    {
        list($main_key, $after_key) = self::_getPrimaryKeyClauses();
        $dataType                   = self::$_type === 'pgsql' ? 'TEXT' : 'BLOB';
        self::$_db->exec(
            'CREATE TABLE ' . self::_sanitizeIdentifier('paste') . ' ( ' .
            "dataid CHAR(16) NOT NULL$main_key, " .
            "data $dataType, " .
            'postdate INT, ' .
            'expiredate INT, ' .
            'opendiscussion INT, ' .
            'burnafterreading INT, ' .
            'meta TEXT, ' .
            'attachment ' . (self::$_type === 'pgsql' ? 'TEXT' : 'MEDIUMBLOB') . ', ' .
            "attachmentname $dataType$after_key );"
        );
    }

    /**
     * create the paste table
     *
     * @access private
     * @static
     * @return void
     */
    private static function _createCommentTable()
    {
        list($main_key, $after_key) = self::_getPrimaryKeyClauses();
        $dataType                   = self::$_type === 'pgsql' ? 'text' : 'BLOB';
        self::$_db->exec(
            'CREATE TABLE ' . self::_sanitizeIdentifier('comment') . ' ( ' .
            "dataid CHAR(16) NOT NULL$main_key, " .
            'pasteid CHAR(16), ' .
            'parentid CHAR(16), ' .
            "data $dataType, " .
            "nickname $dataType, " .
            "vizhash $dataType, " .
            "postdate INT$after_key );"
        );
        self::$_db->exec(
            'CREATE INDEX IF NOT EXISTS comment_parent ON ' .
            self::_sanitizeIdentifier('comment') . '(pasteid);'
        );
    }

    /**
     * create the paste table
     *
     * @access private
     * @static
     * @return void
     */
    private static function _createConfigTable()
    {
        list($main_key, $after_key) = self::_getPrimaryKeyClauses('id');
        self::$_db->exec(
            'CREATE TABLE ' . self::_sanitizeIdentifier('config') .
            " ( id CHAR(16) NOT NULL$main_key, value TEXT$after_key );"
        );
        self::_exec(
            'INSERT INTO ' . self::_sanitizeIdentifier('config') .
            ' VALUES(?,?)',
            array('VERSION', PrivateBin::VERSION)
        );
    }

    /**
     * sanitizes identifiers
     *
     * @access private
     * @static
     * @param  string $identifier
     * @return string
     */
    private static function _sanitizeIdentifier($identifier)
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', self::$_prefix . $identifier);
    }

    /**
     * upgrade the database schema from an old version
     *
     * @access private
     * @static
     * @param  string $oldversion
     * @return void
     */
    private static function _upgradeDatabase($oldversion)
    {
        $dataType = self::$_type === 'pgsql' ? 'TEXT' : 'BLOB';
        switch ($oldversion) {
            case '0.21':
                // create the meta column if necessary (pre 0.21 change)
                try {
                    self::$_db->exec('SELECT meta FROM ' . self::_sanitizeIdentifier('paste') . ' LIMIT 1;');
                } catch (PDOException $e) {
                    self::$_db->exec('ALTER TABLE ' . self::_sanitizeIdentifier('paste') . ' ADD COLUMN meta TEXT;');
                }
                // SQLite only allows one ALTER statement at a time...
                self::$_db->exec(
                    'ALTER TABLE ' . self::_sanitizeIdentifier('paste') .
                    ' ADD COLUMN attachment ' .
                    (self::$_type === 'pgsql' ? 'TEXT' : 'MEDIUMBLOB') . ';'
                );
                self::$_db->exec(
                    'ALTER TABLE ' . self::_sanitizeIdentifier('paste') . " ADD COLUMN attachmentname $dataType;"
                );
                // SQLite doesn't support MODIFY, but it allows TEXT of similar
                // size as BLOB, so there is no need to change it there
                if (self::$_type !== 'sqlite') {
                    self::$_db->exec(
                        'ALTER TABLE ' . self::_sanitizeIdentifier('paste') .
                        ' ADD PRIMARY KEY (dataid), MODIFY COLUMN data $dataType;'
                    );
                    self::$_db->exec(
                        'ALTER TABLE ' . self::_sanitizeIdentifier('comment') .
                        " ADD PRIMARY KEY (dataid), MODIFY COLUMN data $dataType, " .
                        "MODIFY COLUMN nickname $dataType, MODIFY COLUMN vizhash $dataType;"
                    );
                } else {
                    self::$_db->exec(
                        'CREATE UNIQUE INDEX IF NOT EXISTS paste_dataid ON ' .
                        self::_sanitizeIdentifier('paste') . '(dataid);'
                    );
                    self::$_db->exec(
                        'CREATE UNIQUE INDEX IF NOT EXISTS comment_dataid ON ' .
                        self::_sanitizeIdentifier('comment') . '(dataid);'
                    );
                }
                self::$_db->exec(
                    'CREATE INDEX IF NOT EXISTS comment_parent ON ' .
                    self::_sanitizeIdentifier('comment') . '(pasteid);'
                );
                // no break, continue with updates for 0.22
            case '0.22':
            case '1.0':
                self::_exec(
                    'UPDATE ' . self::_sanitizeIdentifier('config') .
                    ' SET value = ? WHERE id = ?',
                    array(PrivateBin::VERSION, 'VERSION')
                );
        }
    }
}
