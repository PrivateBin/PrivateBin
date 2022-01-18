<?php
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   1.3.5
 */

namespace PrivateBin\Data;

use Exception;
use PDO;
use PDOException;
use PrivateBin\Controller;
use PrivateBin\Json;

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
    public static function getInstance(array $options)
    {
        // if needed initialize the singleton
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }

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
            $db_version = Controller::VERSION;
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
            if (version_compare($db_version, Controller::VERSION, '<')) {
                self::_upgradeDatabase($db_version);
            }
        } else {
            throw new Exception(
                'Missing configuration for key dsn, usr, pwd or opt in the section model_options, please check your configuration file', 6
            );
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
        if (
            array_key_exists($pasteid, self::$_cache)
        ) {
            if (false !== self::$_cache[$pasteid]) {
                return false;
            } else {
                unset(self::$_cache[$pasteid]);
            }
        }

        $expire_date      = 0;
        $opendiscussion   = $burnafterreading = false;
        $attachment       = $attachmentname   = null;
        $meta             = $paste['meta'];
        $isVersion1       = array_key_exists('data', $paste);
        list($createdKey) = self::_getVersionedKeys($isVersion1 ? 1 : 2);
        $created          = (int) $meta[$createdKey];
        unset($meta[$createdKey], $paste['meta']);
        if (array_key_exists('expire_date', $meta)) {
            $expire_date = (int) $meta['expire_date'];
            unset($meta['expire_date']);
        }
        if (array_key_exists('opendiscussion', $meta)) {
            $opendiscussion = $meta['opendiscussion'];
            unset($meta['opendiscussion']);
        }
        if (array_key_exists('burnafterreading', $meta)) {
            $burnafterreading = $meta['burnafterreading'];
            unset($meta['burnafterreading']);
        }
        if ($isVersion1) {
            if (array_key_exists('attachment', $meta)) {
                $attachment = $meta['attachment'];
                unset($meta['attachment']);
            }
            if (array_key_exists('attachmentname', $meta)) {
                $attachmentname = $meta['attachmentname'];
                unset($meta['attachmentname']);
            }
        } else {
            $opendiscussion   = $paste['adata'][2];
            $burnafterreading = $paste['adata'][3];
        }
        try {
            $big_string       = $isVersion1 ? $paste['data'] : Json::encode($paste); 
            if (self::$_type === 'oci') {
                # It is not possible to execute in the normal way if strlen($big_string) >= 4000
                $stmt = self::$_db->prepare(
                    'INSERT INTO ' . self::_sanitizeIdentifier('paste') .
                    ' VALUES(?,?,?,?,?,?,?,?,?)'
                );
                $stmt->bindParam(1, $pasteid);
                $stmt->bindParam(2, $big_string, PDO::PARAM_STR, strlen($big_string));
                $stmt->bindParam(3, $created, PDO::PARAM_INT);
                $stmt->bindParam(4, $expire_date, PDO::PARAM_INT);
                $stmt->bindParam(5, $opendiscussion, PDO::PARAM_INT);
                $stmt->bindParam(6, $burnafterreading, PDO::PARAM_INT);
                $stmt->bindParam(7, Json::encode($meta));
                $stmt->bindParam(8, $attachment, PDO::PARAM_STR, strlen($attachment));
                $stmt->bindParam(9, $attachmentname);
                return $stmt->execute();
            }
            return self::_exec(
                'INSERT INTO ' . self::_sanitizeIdentifier('paste') .
                ' VALUES(?,?,?,?,?,?,?,?,?)',
                array(
                    $pasteid,
                    $big_string,
                    $created,
                    $expire_date,
                    (int) $opendiscussion,
                    (int) $burnafterreading,
                    Json::encode($meta),
                    $attachment,
                    $attachmentname,
                )
            );
        } catch (Exception $e) {
            return false;
        }
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
        if (array_key_exists($pasteid, self::$_cache)) {
            return self::$_cache[$pasteid];
        }

        self::$_cache[$pasteid] = false;
        $rawData    = "";
        try {
            $paste = self::_select(
                'SELECT * FROM ' . self::_sanitizeIdentifier('paste') .
                ' WHERE dataid = ?', array($pasteid), true
            );
            if ($paste !== false) {
                $rawData = self::$_type === 'oci' ? self::_clob($paste['DATA']) : $paste['data'];
            }
        } catch (Exception $e) {
            $paste = false;
        }
        if ($paste === false) {
            return false;
        }
        // create array
        $data       = Json::decode($rawData);
        $isVersion2 = array_key_exists('v', $data) && $data['v'] >= 2;
        if ($isVersion2) {
            self::$_cache[$pasteid] = $data;
            list($createdKey)       = self::_getVersionedKeys(2);
        } else {
            self::$_cache[$pasteid] = array('data' => $paste[self::_sanitizeColumn('data')]);
            list($createdKey)       = self::_getVersionedKeys(1);
        }

        try {
            $paste['meta'] = Json::decode($paste[self::_sanitizeColumn('meta')]);
        } catch (Exception $e) {
            $paste['meta'] = array();
        }
        $paste                                       = self::upgradePreV1Format($paste);
        self::$_cache[$pasteid]['meta']              = $paste['meta'];
        self::$_cache[$pasteid]['meta'][$createdKey] = (int) $paste[self::_sanitizeColumn('postdate')];
        $expire_date                                 = (int) $paste[self::_sanitizeColumn('expiredate')];
        if ($expire_date > 0) {
            self::$_cache[$pasteid]['meta']['expire_date'] = $expire_date;
        }
        if ($isVersion2) {
            return self::$_cache[$pasteid];
        }

        // support v1 attachments
        if (array_key_exists(self::_sanitizeColumn('attachment'), $paste) && strlen($paste[self::_sanitizeColumn('attachment')])) {
            self::$_cache[$pasteid]['attachment'] = $paste[self::_sanitizeColumn('attachment')];
            if (array_key_exists(self::_sanitizeColumn('attachmentname'), $paste) && strlen($paste[self::_sanitizeColumn('attachmentname')])) {
                self::$_cache[$pasteid]['attachmentname'] = $paste[self::_sanitizeColumn('attachmentname')];
            }
        }
        if ($paste[self::_sanitizeColumn('opendiscussion')]) {
            self::$_cache[$pasteid]['meta']['opendiscussion'] = true;
        }
        if ($paste[self::_sanitizeColumn('burnafterreading')]) {
            self::$_cache[$pasteid]['meta']['burnafterreading'] = true;
        }

        return self::$_cache[$pasteid];
    }

    /**
     * Delete a paste and its discussion.
     *
     * @access public
     * @param  string $pasteid
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
    public function createComment($pasteid, $parentid, $commentid, array $comment)
    {
        if (array_key_exists('data', $comment)) {
            $version = 1;
            $data    = $comment['data'];
        } else {
            $version = 2;
            $data    = Json::encode($comment);
        }
        list($createdKey, $iconKey) = self::_getVersionedKeys($version);
        $meta                       = $comment['meta'];
        unset($comment['meta']);
        foreach (array('nickname', $iconKey) as $key) {
            if (!array_key_exists($key, $meta)) {
                $meta[$key] = null;
            }
        }
        try {
            if (self::$_type === 'oci') {
                # It is not possible to execute in the normal way if strlen($big_string) >= 4000
                $stmt = self::$_db->prepare(
                    'INSERT INTO ' . self::_sanitizeIdentifier('comment') .
                    ' VALUES(?,?,?,?,?,?,?)'
                );
                $stmt->bindParam(1, $commentid);
                $stmt->bindParam(2, $pasteid);
                $stmt->bindParam(3, $parentid);
                $stmt->bindParam(4, $data, PDO::PARAM_STR, strlen($data));
                $stmt->bindParam(5, $meta['nickname']);
                $stmt->bindParam(6, $meta[$iconKey]);
                $stmt->bindParam(7, $meta[$createdKey], PDO::PARAM_INT);
                return $stmt->execute();
            }
            return self::_exec(
                'INSERT INTO ' . self::_sanitizeIdentifier('comment') .
                ' VALUES(?,?,?,?,?,?,?)',
                array(
                    $commentid,
                    $pasteid,
                    $parentid,
                    $data,
                    $meta['nickname'],
                    $meta[$iconKey],
                    $meta[$createdKey],
                )
            );
        } catch (Exception $e) {
            return false;
        }
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
                $i      = $this->getOpenSlot($comments, (int) $row[self::_sanitizeColumn('postdate')]);
                $id     = $row[self::_sanitizeColumn('dataid')];
                if (self::$_type === 'oci') {
                    $newrow = self::_select(
                        'SELECT data FROM ' . self::_sanitizeIdentifier('comment') .
                        ' WHERE dataid = ?', array($id), true
                    );
                    $rawData = self::_clob($newrow['DATA']);
                }
                else {
                    $rawData = $row['data'];
                }
                $data   = Json::decode($rawData);
                if (array_key_exists('v', $data) && $data['v'] >= 2) {
                    $version      = 2;
                    $comments[$i] = $data;
                } else {
                    $version      = 1;
                    $comments[$i] = array('data' => $rawData);
                }
                list($createdKey, $iconKey) = self::_getVersionedKeys($version);
                $comments[$i]['id']         = $id;
                $comments[$i]['parentid']   = $row[self::_sanitizeColumn('parentid')];
                $comments[$i]['meta']       = array($createdKey => (int) $row[self::_sanitizeColumn('postdate')]);
                foreach (array('nickname' => 'nickname', 'vizhash' => $iconKey) as $rowKey => $commentKey) {
                    if (array_key_exists(self::_sanitizeColumn($rowKey), $row) && !empty($row[self::_sanitizeColumn($rowKey)])) {
                        $comments[$i]['meta'][$commentKey] = $row[self::_sanitizeColumn($rowKey)];
                    }
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
        try {
            return (bool) self::_select(
                'SELECT dataid FROM ' . self::_sanitizeIdentifier('comment') .
                ' WHERE pasteid = ? AND parentid = ? AND dataid = ?',
                array($pasteid, $parentid, $commentid), true
            );
        } catch (Exception $e) {
            return false;
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
    public function setValue($value, $namespace, $key = '')
    {
        if ($namespace === 'traffic_limiter') {
            self::$_last_cache[$key] = $value;
            try {
                $value = Json::encode(self::$_last_cache);
            } catch (Exception $e) {
                return false;
            }
        }
        return self::_exec(
            'UPDATE ' . self::_sanitizeIdentifier('config') .
            ' SET value = ? WHERE id = ?',
            array($value, strtoupper($namespace))
        );
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
        $configKey = strtoupper($namespace);
        $value     = $this->_getConfig($configKey);
        if ($value === '') {
            // initialize the row, so that setValue can rely on UPDATE queries
            self::_exec(
                'INSERT INTO ' . self::_sanitizeIdentifier('config') .
                ' VALUES(?,?)',
                array($configKey, '')
            );

            // migrate filesystem based salt into database
            $file = 'data' . DIRECTORY_SEPARATOR . 'salt.php';
            if ($namespace === 'salt' && is_readable($file)) {
                $value = Filesystem::getInstance(array('dir' => 'data'))->getValue('salt');
                $this->setValue($value, 'salt');
                @unlink($file);
                return $value;
            }
        }
        if ($value && $namespace === 'traffic_limiter') {
            try {
                self::$_last_cache = Json::decode($value);
            } catch (Exception $e) {
                self::$_last_cache = array();
            }
            if (array_key_exists($key, self::$_last_cache)) {
                return self::$_last_cache[$key];
            }
        }
        return (string) $value;
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
            ' WHERE expiredate < ? AND expiredate != ? ' .
            (self::$_type === 'oci' ? 'FETCH NEXT ? ROWS ONLY' : 'LIMIT ?'),
            array(time(), 0, $batchsize)
        );
        if (count($rows)) {
            foreach ($rows as $row) {
                $pastes[] = $row[self::_sanitizeIdentifier('dataid')];
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
     * @return array|false
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
     * get version dependent key names
     *
     * @access private
     * @static
     * @param  int $version
     * @return array
     */
    private static function _getVersionedKeys($version)
    {
        if ($version === 1) {
            return array('postdate', 'vizhash');
        }
        return array('created', 'icon');
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
     * @return string
     */
    private static function _getConfig($key)
    {
        try {
            $row = self::_select(
                'SELECT value FROM ' . self::_sanitizeIdentifier('config') .
                ' WHERE id = ?', array($key), true
            );
        } catch (PDOException $e) {
            return '';
        }
        return $row ? $row[self::_sanitizeColumn('value')] : '';
    }

    /**
     * OCI cannot accept semicolons
     *
     * @access private
     * @static
     * @return string
     */
    private static function _getSemicolon()
    {
        return self::$_type === 'oci' ? "" : ";";
    }

    /**
     * get the primary key clauses, depending on the database driver
     *
     * @access private
     * @static
     * @param  string $key
     * @return array
     */
    private static function _getPrimaryKeyClauses($key = 'dataid')
    {
        $main_key = $after_key = '';
        if (self::$_type === 'mysql' || self::$_type === 'oci') {
            $after_key = ", PRIMARY KEY ($key)";
        } else {
            $main_key = ' PRIMARY KEY';
        }
        return array($main_key, $after_key);
    }

    /**
     * get the data type, depending on the database driver
     *
     * PostgreSQL uses a different API for BLOBs then SQL, hence we use TEXT
     *
     * @access private
     * @static
     * @return string
     */
    private static function _getDataType()
    {
        return self::$_type === 'pgsql' ? 'TEXT' : (self::$_type === 'oci' ? 'VARCHAR2(4000)' : 'BLOB');
    }

    /**
     * get the attachment type, depending on the database driver
     *
     * PostgreSQL and OCI use different APIs for BLOBs then SQL, hence we use TEXT and CLOB
     *
     * @access private
     * @static
     * @return string
     */
    private static function _getAttachmentType()
    {
        return self::$_type === 'pgsql' ? 'TEXT' : (self::$_type === 'oci' ? 'CLOB' : 'MEDIUMBLOB');
    }

    /**
     * get the meta type, depending on the database driver
     *
     * OCI can't even accept TEXT so it has to be VARCHAR2(200)
     *
     * @access private
     * @static
     * @return string
     */
    private static function _getMetaType()
    {
        return self::$_type === 'oci' ? 'VARCHAR2(4000)' : 'TEXT';
    }

    /**
     * create the paste table
     *
     * @access private
     * @static
     */
    private static function _createPasteTable()
    {
        list($main_key, $after_key) = self::_getPrimaryKeyClauses();
        $dataType                   = self::_getDataType();
        $attachmentType             = self::_getAttachmentType();
        $metaType                   = self::_getMetaType();
        self::$_db->exec(
            'CREATE TABLE ' . self::_sanitizeIdentifier('paste') . ' ( ' .
            "dataid CHAR(16) NOT NULL$main_key, " .
            "data $attachmentType, " .
            'postdate INT, ' .
            'expiredate INT, ' .
            'opendiscussion INT, ' .
            'burnafterreading INT, ' .
            "meta $metaType, " .
            "attachment $attachmentType, " .
            "attachmentname $dataType$after_key )" . self::_getSemicolon()
        );
    }

    /**
     * get the nullable text type, depending on the database driver
     *
     * OCI will pad CHAR columns with spaces, hence VARCHAR2
     *
     * @access private
     * @static
     * @return string
     */
    private static function _getParentType()
    {
        return self::$_type === 'oci' ? 'VARCHAR2(16)' : 'CHAR(16)';
    }

    /**
     * create the paste table
     *
     * @access private
     * @static
     */
    private static function _createCommentTable()
    {
        list($main_key, $after_key) = self::_getPrimaryKeyClauses();
        $dataType                   = self::_getDataType();
        $parentType                 = self::_getParentType();
        $attachmentType             = self::_getAttachmentType();
        self::$_db->exec(
            'CREATE TABLE ' . self::_sanitizeIdentifier('comment') . ' ( ' .
            "dataid CHAR(16) NOT NULL$main_key, " .
            'pasteid CHAR(16), ' .
            "parentid $parentType, " .
            "data $attachmentType, " .
            "nickname $dataType, " .
            "vizhash $dataType, " .
            "postdate INT$after_key )" . self::_getSemicolon()
        );
        self::$_db->exec(
            'CREATE INDEX comment_parent ON ' .
            self::_sanitizeIdentifier('comment') . '(pasteid)' . self::_getSemicolon()
        );
    }

    /**
     * create the paste table
     *
     * @access private
     * @static
     */
    private static function _createConfigTable()
    {
        list($main_key, $after_key) = self::_getPrimaryKeyClauses('id');
        $charType                   = self::$_type === 'oci' ? 'VARCHAR2(16)' : 'CHAR(16)';
        $textType                   = self::$_type === 'oci' ? 'VARCHAR2(4000)' : 'TEXT';
        self::$_db->exec(
            'CREATE TABLE ' . self::_sanitizeIdentifier('config') .
            " ( id $charType NOT NULL$main_key, value $textType$after_key )" . self::_getSemicolon()
        );
        self::_exec(
            'INSERT INTO ' . self::_sanitizeIdentifier('config') .
            ' VALUES(?,?)',
            array('VERSION', Controller::VERSION)
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
        $id = preg_replace('/[^A-Za-z0-9_]+/', '', self::$_prefix . $identifier);
        return self::_sanitizeColumn($id);
    }

    /**
     * sanitizes column name because OCI
     *
     * @access private
     * @static
     * @param  string $name
     * @return string
     */
    private static function _sanitizeColumn($name)
    {
        return self::$_type === 'oci' ? strtoupper($name) : $name;
    }

    /**
     * upgrade the database schema from an old version
     *
     * @access private
     * @static
     * @param  string $oldversion
     */
    private static function _upgradeDatabase($oldversion)
    {
        $dataType       = self::_getDataType();
        $attachmentType = self::_getAttachmentType();
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
                    " ADD COLUMN attachment $attachmentType;"
                );
                self::$_db->exec(
                    'ALTER TABLE ' . self::_sanitizeIdentifier('paste') . " ADD COLUMN attachmentname $dataType;"
                );
                // SQLite doesn't support MODIFY, but it allows TEXT of similar
                // size as BLOB, so there is no need to change it there
                if (self::$_type !== 'sqlite') {
                    self::$_db->exec(
                        'ALTER TABLE ' . self::_sanitizeIdentifier('paste') .
                        " ADD PRIMARY KEY (dataid), MODIFY COLUMN data $dataType;"
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
                // no break, continue with updates for 0.22 and later
            case '1.3':
                // SQLite doesn't support MODIFY, but it allows TEXT of similar
                // size as BLOB and PostgreSQL uses TEXT, so there is no need
                // to change it there
                if (self::$_type !== 'sqlite' && self::$_type !== 'pgsql') {
                    self::$_db->exec(
                        'ALTER TABLE ' . self::_sanitizeIdentifier('paste') .
                        " MODIFY COLUMN data $attachmentType;"
                    );
                }
                // no break, continue with updates for all newer versions
            default:
                self::_exec(
                    'UPDATE ' . self::_sanitizeIdentifier('config') .
                    ' SET value = ? WHERE id = ?',
                    array(Controller::VERSION, 'VERSION')
                );
        }
    }

    /**
     * read CLOB for OCI
     * https://stackoverflow.com/questions/36200534/pdo-oci-into-a-clob-field
     *
     * @access private
     * @static
     * @param  object $column
     * @return string
     */
    private static function _clob($column)
    {
        if ($column == null) return null;
        $str = "";
        while ($column !== null and $tmp = fread($column, 1024))
            $str .= $tmp;
        return $str;
    }
}
