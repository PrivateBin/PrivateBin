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
     * instance of database connection
     *
     * @access private
     * @var PDO
     */
    private $_db;

    /**
     * table prefix
     *
     * @access private
     * @var string
     */
    private $_prefix = '';

    /**
     * database type
     *
     * @access private
     * @var string
     */
    private $_type = '';

    /**
     * instantiates a new Database data backend
     *
     * @access public
     * @param  array $options
     * @throws Exception
     */
    public function __construct(array $options)
    {
        // set table prefix if given
        if (array_key_exists('tbl', $options)) {
            $this->_prefix = $options['tbl'];
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
            if (!array_key_exists(PDO::ATTR_PERSISTENT, $options['opt'])) {
                $options['opt'][PDO::ATTR_PERSISTENT] = true;
            }
            $db_tables_exist                            = true;

            // setup type and dabase connection
            $this->_type = strtolower(
                substr($options['dsn'], 0, strpos($options['dsn'], ':'))
            );
            // MySQL uses backticks to quote identifiers by default,
            // tell it to expect ANSI SQL double quotes
            if ($this->_type === 'mysql' && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options['opt'][PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION sql_mode='ANSI_QUOTES'";
            }
            $tableQuery = $this->_getTableQuery($this->_type);
            $this->_db  = new PDO(
                $options['dsn'],
                $options['usr'],
                $options['pwd'],
                $options['opt']
            );

            // check if the database contains the required tables
            $tables = $this->_db->query($tableQuery)->fetchAll(PDO::FETCH_COLUMN, 0);

            // create paste table if necessary
            if (!in_array($this->_sanitizeIdentifier('paste'), $tables)) {
                $this->_createPasteTable();
                $db_tables_exist = false;
            }

            // create comment table if necessary
            if (!in_array($this->_sanitizeIdentifier('comment'), $tables)) {
                $this->_createCommentTable();
                $db_tables_exist = false;
            }

            // create config table if necessary
            $db_version = Controller::VERSION;
            if (!in_array($this->_sanitizeIdentifier('config'), $tables)) {
                $this->_createConfigTable();
                // if we only needed to create the config table, the DB is older then 0.22
                if ($db_tables_exist) {
                    $db_version = '0.21';
                }
            } else {
                $db_version = $this->_getConfig('VERSION');
            }

            // update database structure if necessary
            if (version_compare($db_version, Controller::VERSION, '<')) {
                $this->_upgradeDatabase($db_version);
            }
        } else {
            throw new Exception(
                'Missing configuration for key dsn, usr, pwd or opt in the section model_options, please check your configuration file', 6
            );
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
        $expire_date      = 0;
        $opendiscussion   = $burnafterreading = false;
        $attachment       = $attachmentname   = null;
        $meta             = $paste['meta'];
        $isVersion1       = array_key_exists('data', $paste);
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
            return $this->_exec(
                'INSERT INTO "' . $this->_sanitizeIdentifier('paste') .
                '" VALUES(?,?,?,?,?,?,?,?)',
                array(
                    $pasteid,
                    $isVersion1 ? $paste['data'] : Json::encode($paste),
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
        try {
            $row = $this->_select(
                'SELECT * FROM "' . $this->_sanitizeIdentifier('paste') .
                '" WHERE "dataid" = ?', array($pasteid), true
            );
        } catch (Exception $e) {
            $row = false;
        }
        if ($row === false) {
            return false;
        }
        // create array
        $data       = Json::decode($row['data']);
        $isVersion2 = array_key_exists('v', $data) && $data['v'] >= 2;
        $paste      = $isVersion2 ? $data : array('data' => $row['data']);

        try {
            $row['meta'] = Json::decode($row['meta']);
        } catch (Exception $e) {
            $row['meta'] = array();
        }
        $row                        = self::upgradePreV1Format($row);
        $paste['meta']              = $row['meta'];
        $expire_date                = (int) $row['expiredate'];
        if ($expire_date > 0) {
            $paste['meta']['expire_date'] = $expire_date;
        }
        if ($isVersion2) {
            return $paste;
        }

        // support v1 attachments
        if (array_key_exists('attachment', $row) && !empty($row['attachment'])) {
            $paste['attachment'] = $row['attachment'];
            if (array_key_exists('attachmentname', $row) && !empty($row['attachmentname'])) {
                $paste['attachmentname'] = $row['attachmentname'];
            }
        }
        if ($row['opendiscussion']) {
            $paste['meta']['opendiscussion'] = true;
        }
        if ($row['burnafterreading']) {
            $paste['meta']['burnafterreading'] = true;
        }

        return $paste;
    }

    /**
     * Delete a paste and its discussion.
     *
     * @access public
     * @param  string $pasteid
     */
    public function delete($pasteid)
    {
        $this->_exec(
            'DELETE FROM "' . $this->_sanitizeIdentifier('paste') .
            '" WHERE "dataid" = ?', array($pasteid)
        );
        $this->_exec(
            'DELETE FROM "' . $this->_sanitizeIdentifier('comment') .
            '" WHERE "pasteid" = ?', array($pasteid)
        );
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
        try {
            $row = $this->_select(
                'SELECT "dataid" FROM "' . $this->_sanitizeIdentifier('paste') .
                '" WHERE "dataid" = ?', array($pasteid), true
            );
        } catch (Exception $e) {
            return false;
        }
        return (bool) $row;
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
        list($createdKey, $iconKey) = $this->_getVersionedKeys($version);
        $meta                       = $comment['meta'];
        unset($comment['meta']);
        foreach (array('nickname', $iconKey) as $key) {
            if (!array_key_exists($key, $meta)) {
                $meta[$key] = null;
            }
        }
        try {
            return $this->_exec(
                'INSERT INTO "' . $this->_sanitizeIdentifier('comment') .
                '" VALUES(?,?,?,?,?,?,?)',
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
        $rows = $this->_select(
            'SELECT * FROM "' . $this->_sanitizeIdentifier('comment') .
            '" WHERE "pasteid" = ?', array($pasteid)
        );

        // create comment list
        $comments = array();
        if (is_array($rows) && count($rows)) {
            foreach ($rows as $row) {
                $i    = $this->getOpenSlot($comments, (int) $row['postdate']);
                $data = Json::decode($row['data']);
                if (array_key_exists('v', $data) && $data['v'] >= 2) {
                    $version      = 2;
                    $comments[$i] = $data;
                } else {
                    $version      = 1;
                    $comments[$i] = array('data' => $row['data']);
                }
                list($createdKey, $iconKey) = $this->_getVersionedKeys($version);
                $comments[$i]['id']         = $row['dataid'];
                $comments[$i]['parentid']   = $row['parentid'];
                $comments[$i]['meta']       = array($createdKey => (int) $row['postdate']);
                foreach (array('nickname' => 'nickname', 'vizhash' => $iconKey) as $rowKey => $commentKey) {
                    if (array_key_exists($rowKey, $row) && !empty($row[$rowKey])) {
                        $comments[$i]['meta'][$commentKey] = $row[$rowKey];
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
            return (bool) $this->_select(
                'SELECT "dataid" FROM "' . $this->_sanitizeIdentifier('comment') .
                '" WHERE "pasteid" = ? AND "parentid" = ? AND "dataid" = ?',
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
            $this->_last_cache[$key] = $value;
            try {
                $value = Json::encode($this->_last_cache);
            } catch (Exception $e) {
                return false;
            }
        }
        return $this->_exec(
            'UPDATE "' . $this->_sanitizeIdentifier('config') .
            '" SET "value" = ? WHERE "id" = ?',
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
            $this->_exec(
                'INSERT INTO "' . $this->_sanitizeIdentifier('config') .
                '" VALUES(?,?)',
                array($configKey, '')
            );

            // migrate filesystem based salt into database
            $file = 'data' . DIRECTORY_SEPARATOR . 'salt.php';
            if ($namespace === 'salt' && is_readable($file)) {
                $fs    = new Filesystem(array('dir' => 'data'));
                $value = $fs->getValue('salt');
                $this->setValue($value, 'salt');
                @unlink($file);
                return $value;
            }
        }
        if ($value && $namespace === 'traffic_limiter') {
            try {
                $this->_last_cache = Json::decode($value);
            } catch (Exception $e) {
                $this->_last_cache = array();
            }
            if (array_key_exists($key, $this->_last_cache)) {
                return $this->_last_cache[$key];
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
        $statement = $this->_db->prepare(
            'SELECT "dataid" FROM "' . $this->_sanitizeIdentifier('paste') .
            '" WHERE "expiredate" < ? AND "expiredate" != ? ' .
            ($this->_type === 'oci' ? 'FETCH NEXT ? ROWS ONLY' : 'LIMIT ?')
        );
        $statement->execute(array(time(), 0, $batchsize));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * @inheritDoc
     */
    public function getAllPastes()
    {
        return $this->_db->query(
            'SELECT "dataid" FROM "' . $this->_sanitizeIdentifier('paste') . '"'
        )->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * execute a statement
     *
     * @access private
     * @param  string $sql
     * @param  array $params
     * @throws PDOException
     * @return bool
     */
    private function _exec($sql, array $params)
    {
        $statement = $this->_db->prepare($sql);
        $position  = 1;
        foreach ($params as &$parameter) {
            if (is_int($parameter)) {
                $statement->bindParam($position, $parameter, PDO::PARAM_INT);
            } elseif (is_string($parameter) && strlen($parameter) >= 4000) {
                $statement->bindParam($position, $parameter, PDO::PARAM_STR, strlen($parameter));
            } else {
                $statement->bindParam($position, $parameter);
            }
            ++$position;
        }
        $result = $statement->execute();
        $statement->closeCursor();
        return $result;
    }

    /**
     * run a select statement
     *
     * @access private
     * @param  string $sql
     * @param  array $params
     * @param  bool $firstOnly if only the first row should be returned
     * @throws PDOException
     * @return array|false
     */
    private function _select($sql, array $params, $firstOnly = false)
    {
        $statement = $this->_db->prepare($sql);
        $statement->execute($params);
        if ($firstOnly) {
            $result = $statement->fetch(PDO::FETCH_ASSOC);
        } elseif ($this->_type === 'oci') {
            // workaround for https://bugs.php.net/bug.php?id=46728
            $result = array();
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array_map('PrivateBin\Data\Database::_sanitizeClob', $row);
            }
        } else {
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        $statement->closeCursor();
        if ($this->_type === 'oci' && is_array($result)) {
            // returned CLOB values are streams, convert these into strings
            $result = $firstOnly ?
                array_map('PrivateBin\Data\Database::_sanitizeClob', $result) :
                $result;
        }
        return $result;
    }

    /**
     * get version dependent key names
     *
     * @access private
     * @param  int $version
     * @return array
     */
    private function _getVersionedKeys($version)
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
     * @param  string $type
     * @throws Exception
     * @return string
     */
    private function _getTableQuery($type)
    {
        switch ($type) {
            case 'ibm':
                $sql = 'SELECT "tabname" FROM "SYSCAT"."TABLES"';
                break;
            case 'informix':
                $sql = 'SELECT "tabname" FROM "systables"';
                break;
            case 'mssql':
                // U: tables created by the user
                $sql = 'SELECT "name" FROM "sysobjects" '
                     . 'WHERE "type" = \'U\' ORDER BY "name"';
                break;
            case 'mysql':
                $sql = 'SHOW TABLES';
                break;
            case 'oci':
                $sql = 'SELECT table_name FROM all_tables';
                break;
            case 'pgsql':
                $sql = 'SELECT "tablename" FROM "pg_catalog"."pg_tables" '
                     . 'WHERE "schemaname" NOT IN (\'pg_catalog\', \'information_schema\')';
                break;
            case 'sqlite':
                $sql = 'SELECT "name" FROM "sqlite_master" WHERE "type"=\'table\' '
                     . 'UNION ALL SELECT "name" FROM "sqlite_temp_master" '
                     . 'WHERE "type"=\'table\' ORDER BY "name"';
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
     * @param  string $key
     * @return string
     */
    private function _getConfig($key)
    {
        try {
            $row = $this->_select(
                'SELECT "value" FROM "' . $this->_sanitizeIdentifier('config') .
                '" WHERE "id" = ?', array($key), true
            );
        } catch (PDOException $e) {
            return '';
        }
        return $row ? $row['value'] : '';
    }

    /**
     * get the primary key clauses, depending on the database driver
     *
     * @access private
     * @param  string $key
     * @return array
     */
    private function _getPrimaryKeyClauses($key = 'dataid')
    {
        $main_key = $after_key = '';
        switch ($this->_type) {
            case 'mysql':
            case 'oci':
                $after_key = ", PRIMARY KEY (\"$key\")";
                break;
            default:
                $main_key = ' PRIMARY KEY';
                break;
        }
        return array($main_key, $after_key);
    }

    /**
     * get the data type, depending on the database driver
     *
     * PostgreSQL and OCI uses a different API for BLOBs then SQL, hence we use TEXT and CLOB
     *
     * @access private
     * @return string
     */
    private function _getDataType()
    {
        switch ($this->_type) {
            case 'oci':
                return 'CLOB';
            case 'pgsql':
                return 'TEXT';
            default:
                return 'BLOB';
        }
    }

    /**
     * get the attachment type, depending on the database driver
     *
     * PostgreSQL and OCI use different APIs for BLOBs then SQL, hence we use TEXT and CLOB
     *
     * @access private
     * @return string
     */
    private function _getAttachmentType()
    {
        switch ($this->_type) {
            case 'oci':
                return 'CLOB';
            case 'pgsql':
                return 'TEXT';
            default:
                return 'MEDIUMBLOB';
        }
    }

    /**
     * get the meta type, depending on the database driver
     *
     * OCI doesn't accept TEXT so it has to be VARCHAR2(4000)
     *
     * @access private
     * @return string
     */
    private function _getMetaType()
    {
        switch ($this->_type) {
            case 'oci':
                return 'VARCHAR2(4000)';
            default:
                return 'TEXT';
        }
    }

    /**
     * create the paste table
     *
     * @access private
     */
    private function _createPasteTable()
    {
        list($main_key, $after_key) = $this->_getPrimaryKeyClauses();
        $dataType                   = $this->_getDataType();
        $attachmentType             = $this->_getAttachmentType();
        $metaType                   = $this->_getMetaType();
        $this->_db->exec(
            'CREATE TABLE "' . $this->_sanitizeIdentifier('paste') . '" ( ' .
            "\"dataid\" CHAR(16) NOT NULL$main_key, " .
            "\"data\" $attachmentType, " .
            '"expiredate" INT, ' .
            '"opendiscussion" INT, ' .
            '"burnafterreading" INT, ' .
            "\"meta\" $metaType, " .
            "\"attachment\" $attachmentType, " .
            "\"attachmentname\" $dataType$after_key )"
        );
    }

    /**
     * create the paste table
     *
     * @access private
     */
    private function _createCommentTable()
    {
        list($main_key, $after_key) = $this->_getPrimaryKeyClauses();
        $dataType                   = $this->_getDataType();
        $this->_db->exec(
            'CREATE TABLE "' . $this->_sanitizeIdentifier('comment') . '" ( ' .
            "\"dataid\" CHAR(16) NOT NULL$main_key, " .
            '"pasteid" CHAR(16), ' .
            '"parentid" CHAR(16), ' .
            "\"data\" $dataType, " .
            "\"nickname\" $dataType, " .
            "\"vizhash\" $dataType, " .
            "\"postdate\" INT$after_key )"
        );
        if ($this->_type === 'oci') {
            $this->_db->exec(
                'declare
                    already_exists  exception;
                    columns_indexed exception;
                    pragma exception_init( already_exists, -955 );
                    pragma exception_init(columns_indexed, -1408);
                begin
                    execute immediate \'create index "comment_parent" on "' . $this->_sanitizeIdentifier('comment') . '" ("pasteid")\';
                exception
                    when already_exists or columns_indexed then
                    NULL;
                end;'
            );
        } else {
            // CREATE INDEX IF NOT EXISTS not supported as of Oracle MySQL <= 8.0
            $this->_db->exec(
                'CREATE INDEX "' .
                $this->_sanitizeIdentifier('comment_parent') . '" ON "' .
                $this->_sanitizeIdentifier('comment') . '" ("pasteid")'
            );
        }
    }

    /**
     * create the paste table
     *
     * @access private
     */
    private function _createConfigTable()
    {
        list($main_key, $after_key) = $this->_getPrimaryKeyClauses('id');
        $charType                   = $this->_type === 'oci' ? 'VARCHAR2(16)' : 'CHAR(16)';
        $textType                   = $this->_getMetaType();
        $this->_db->exec(
            'CREATE TABLE "' . $this->_sanitizeIdentifier('config') .
            "\" ( \"id\" $charType NOT NULL$main_key, \"value\" $textType$after_key )"
        );
        $this->_exec(
            'INSERT INTO "' . $this->_sanitizeIdentifier('config') .
            '" VALUES(?,?)',
            array('VERSION', Controller::VERSION)
        );
    }

    /**
     * sanitizes CLOB values used with OCI
     *
     * From: https://stackoverflow.com/questions/36200534/pdo-oci-into-a-clob-field
     *
     * @access public
     * @static
     * @param  int|string|resource $value
     * @return int|string
     */
    public static function _sanitizeClob($value)
    {
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }
        return $value;
    }

    /**
     * sanitizes identifiers
     *
     * @access private
     * @param  string $identifier
     * @return string
     */
    private function _sanitizeIdentifier($identifier)
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $this->_prefix . $identifier);
    }

    /**
     * upgrade the database schema from an old version
     *
     * @access private
     * @param  string $oldversion
     */
    private function _upgradeDatabase($oldversion)
    {
        $dataType       = $this->_getDataType();
        $attachmentType = $this->_getAttachmentType();
        if (version_compare($oldversion, '0.21', '<=')) {
            // create the meta column if necessary (pre 0.21 change)
            try {
                $this->_db->exec(
                    'SELECT "meta" FROM "' . $this->_sanitizeIdentifier('paste') . '" ' .
                    ($this->_type === 'oci' ? 'FETCH NEXT 1 ROWS ONLY' : 'LIMIT 1')
                );
            } catch (PDOException $e) {
                $this->_db->exec('ALTER TABLE "' . $this->_sanitizeIdentifier('paste') . '" ADD COLUMN "meta" TEXT');
            }
            // SQLite only allows one ALTER statement at a time...
            $this->_db->exec(
                'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                "\" ADD COLUMN \"attachment\" $attachmentType"
            );
            $this->_db->exec(
                'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') . "\" ADD COLUMN \"attachmentname\" $dataType"
            );
            // SQLite doesn't support MODIFY, but it allows TEXT of similar
            // size as BLOB, so there is no need to change it there
            if ($this->_type !== 'sqlite') {
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                    "\" ADD PRIMARY KEY (\"dataid\"), MODIFY COLUMN \"data\" $dataType"
                );
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('comment') .
                    "\" ADD PRIMARY KEY (\"dataid\"), MODIFY COLUMN \"data\" $dataType, " .
                    "MODIFY COLUMN \"nickname\" $dataType, MODIFY COLUMN \"vizhash\" $dataType"
                );
            } else {
                $this->_db->exec(
                    'CREATE UNIQUE INDEX IF NOT EXISTS "' .
                    $this->_sanitizeIdentifier('paste_dataid') . '" ON "' .
                    $this->_sanitizeIdentifier('paste') . '" ("dataid")'
                );
                $this->_db->exec(
                    'CREATE UNIQUE INDEX IF NOT EXISTS "' .
                    $this->_sanitizeIdentifier('comment_dataid') . '" ON "' .
                    $this->_sanitizeIdentifier('comment') . '" ("dataid")'
                );
            }
            // CREATE INDEX IF NOT EXISTS not supported as of Oracle MySQL <= 8.0
            $this->_db->exec(
                'CREATE INDEX "' .
                $this->_sanitizeIdentifier('comment_parent') . '" ON "' .
                $this->_sanitizeIdentifier('comment') . '" ("pasteid")'
            );
        }
        if (version_compare($oldversion, '1.3', '<=')) {
            // SQLite doesn't support MODIFY, but it allows TEXT of similar
            // size as BLOB and PostgreSQL uses TEXT, so there is no need
            // to change it there
            if ($this->_type !== 'sqlite' && $this->_type !== 'pgsql') {
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                    "\" MODIFY COLUMN \"data\" $attachmentType"
                );
            }
        }
        if (version_compare($oldversion, '1.7.1', '<=')) {
            $supportsDropColumn = true;
            if ($this->_type === 'sqlite') {
                try {
                    $row                = $this->_select('SELECT sqlite_version() AS "v"', array(), true);
                    $supportsDropColumn = version_compare($row['v'], '3.35.0', '>=');
                } catch (PDOException $e) {
                    $supportsDropColumn = false;
                }
            }
            if ($supportsDropColumn) {
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                    '" DROP COLUMN "postdate"'
                );
            }
        }
        $this->_exec(
            'UPDATE "' . $this->_sanitizeIdentifier('config') .
            '" SET "value" = ? WHERE "id" = ?',
            array(Controller::VERSION, 'VERSION')
        );
    }
}
