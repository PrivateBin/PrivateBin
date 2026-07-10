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
use PrivateBin\Exception\JsonException;
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
            if ($this->_type === 'mysql') {
                // deprecated as of PHP 8.5
                if (version_compare(PHP_VERSION, '8.5') < 0 && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                    $options['opt'][PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION sql_mode='ANSI_QUOTES'";
                } elseif (defined('Pdo\Mysql::ATTR_INIT_COMMAND')) {
                    $options['opt'][Pdo\Mysql::ATTR_INIT_COMMAND] = "SET SESSION sql_mode='ANSI_QUOTES'";
                }
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

            // create user table if necessary (for built-in auth)
            if (!in_array($this->_sanitizeIdentifier('user'), $tables)) {
                $this->_createUserTable();
            } else {
                // migrate existing user table: add new columns if missing
                $this->_migrateUserTable();
            }

            // create user_session table if necessary (for built-in auth)
            if (!in_array($this->_sanitizeIdentifier('user_session'), $tables)) {
                $this->_createUserSessionTable();
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
    public function create($pasteid, array &$paste)
    {
        $expire_date      = 0;
        $meta             = $paste['meta'];
        if (array_key_exists('expire_date', $meta)) {
            $expire_date = (int) $meta['expire_date'];
            unset($meta['expire_date']);
        }
        try {
            return $this->_exec(
                'INSERT INTO "' . $this->_sanitizeIdentifier('paste') .
                '" VALUES(?,?,?,?)',
                array(
                    $pasteid,
                    Json::encode($paste),
                    $expire_date,
                    Json::encode($meta),
                )
            );
        } catch (Exception $e) {
            error_log('Error while attempting to insert a paste into the database: ' . $e->getMessage());
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
        } catch (PDOException $e) {
            $row = false;
        }
        if ($row === false) {
            return false;
        }
        // create array
        try {
            $paste = Json::decode($row['data']);
        } catch (JsonException $e) {
            error_log('Error while reading a paste from the database: ' . $e->getMessage());
            $paste = array();
        }

        try {
            $paste['meta'] = Json::decode($row['meta']);
        } catch (JsonException $e) {
            error_log('Error while reading a paste from the database: ' . $e->getMessage());
            $paste['meta'] = array();
        }
        $expire_date = (int) $row['expiredate'];
        if ($expire_date > 0) {
            $paste['meta']['expire_date'] = $expire_date;
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
        } catch (PDOException $e) {
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
    public function createComment($pasteid, $parentid, $commentid, array &$comment)
    {
        try {
            $data = Json::encode($comment);
        } catch (JsonException $e) {
            error_log('Error while attempting to insert a comment into the database: ' . $e->getMessage());
            return false;
        }
        $meta = $comment['meta'];
        if (!array_key_exists('icon', $meta)) {
            $meta['icon'] = null;
        }
        try {
            return $this->_exec(
                'INSERT INTO "' . $this->_sanitizeIdentifier('comment') .
                '" VALUES(?,?,?,?,?,?)',
                array(
                    $commentid,
                    $pasteid,
                    $parentid,
                    $data,
                    $meta['icon'],
                    $meta['created'],
                )
            );
        } catch (PDOException $e) {
            error_log('Error while attempting to insert a comment into the database: ' . $e->getMessage());
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
        if (count($rows)) {
            foreach ($rows as $row) {
                try {
                    $data = Json::decode($row['data']);
                } catch (JsonException $e) {
                    error_log('Error while reading a comment from the database: ' . $e->getMessage());
                    $data = array();
                }
                $i                          = $this->getOpenSlot($comments, (int) $row['postdate']);
                $comments[$i]               = $data;
                $comments[$i]['id']         = $row['dataid'];
                $comments[$i]['parentid']   = $row['parentid'];
                $comments[$i]['meta']       = array('created' => (int) $row['postdate']);
                if (array_key_exists('vizhash', $row) && !empty($row['vizhash'])) {
                    $comments[$i]['meta']['icon'] = $row['vizhash'];
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
        } catch (PDOException $e) {
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
            } catch (JsonException $e) {
                error_log('Error encoding JSON for table "config", row "traffic_limiter": ' . $e->getMessage());
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
                if (!unlink($file)) {
                    error_log('Error deleting migrated salt: ' . $file);
                }
                return $value;
            }
        }
        if ($value && $namespace === 'traffic_limiter') {
            try {
                $this->_last_cache = Json::decode($value);
            } catch (JsonException $e) {
                error_log('Error decoding JSON from table "config", row "traffic_limiter": ' . $e->getMessage());
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
        try {
            $statement = $this->_db->prepare(
                'SELECT "dataid" FROM "' . $this->_sanitizeIdentifier('paste') .
                '" WHERE "expiredate" < ? AND "expiredate" != ? ' .
                ($this->_type === 'oci' ? 'FETCH NEXT ? ROWS ONLY' : 'LIMIT ?')
            );
            $statement->execute(array(time(), 0, $batchsize));
            return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            error_log('Error while attempting to find expired pastes in the database: ' . $e->getMessage());
            return array();
        }
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
     * @return array
     */
    private function _select($sql, array $params, $firstOnly = false)
    {
        $statement = $this->_db->prepare($sql);
        $statement->execute($params);
        if ($firstOnly) {
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            if ($this->_type === 'oci' && is_array($result)) {
                // returned CLOB values are streams, convert these into strings
                $result = array_map('PrivateBin\Data\Database::_sanitizeClob', $result);
            }
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
        return $result;
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
            error_log('Error while attempting to fetch configuration key "' . $key . '" in the database: ' . $e->getMessage());
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
        $attachmentType             = $this->_getAttachmentType();
        $metaType                   = $this->_getMetaType();
        $this->_db->exec(
            'CREATE TABLE "' . $this->_sanitizeIdentifier('paste') . '" ( ' .
            "\"dataid\" CHAR(16) NOT NULL$main_key, " .
            "\"data\" $attachmentType, " .
            '"expiredate" INT, ' .
            "\"meta\" $metaType$after_key )"
        );
    }

    /**
     * create the comment table
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
     * create the config table
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
     * create the user table for built-in authentication
     *
     * @access private
     */
    private function _createUserTable()
    {
        list($main_key, $after_key) = $this->_getPrimaryKeyClauses('username');
        $metaType                   = $this->_getMetaType();
        $usernameType               = $this->_type === 'oci' ? 'VARCHAR2(255)' : 'VARCHAR(255)';
        $this->_db->exec(
            'CREATE TABLE "' . $this->_sanitizeIdentifier('user') . '" ( ' .
            "\"username\" $usernameType NOT NULL$main_key, " .
            "\"password_hash\" $usernameType NOT NULL, " .
            '"role" VARCHAR(20) NOT NULL DEFAULT \'user\', ' .
            '"is_active" INT NOT NULL DEFAULT 1, ' .
            '"is_approved" INT NOT NULL DEFAULT 1, ' .
            "\"email\" $usernameType DEFAULT '', " .
            '"force_password_change" INT NOT NULL DEFAULT 0, ' .
            "\"reset_token\" $usernameType DEFAULT '', " .
            '"reset_token_expires" INT NOT NULL DEFAULT 0, ' .
            '"created_at" INT NOT NULL DEFAULT 0, ' .
            "\"last_login\" INT NOT NULL DEFAULT 0$after_key )"
        );
    }

    /**
     * create the user session table for built-in authentication
     *
     * @access private
     */
    private function _createUserSessionTable()
    {
        list($main_key, $after_key) = $this->_getPrimaryKeyClauses('id');
        $metaType                   = $this->_getMetaType();
        $idType                     = $this->_type === 'oci' ? 'VARCHAR2(64)' : 'VARCHAR(64)';
        $usernameType               = $this->_type === 'oci' ? 'VARCHAR2(255)' : 'VARCHAR(255)';
        $this->_db->exec(
            'CREATE TABLE "' . $this->_sanitizeIdentifier('user_session') . '" ( ' .
            "\"id\" $idType NOT NULL$main_key, " .
            "\"username\" $usernameType NOT NULL, " .
            '"created_at" INT NOT NULL DEFAULT 0, ' .
            '"expires_at" INT NOT NULL DEFAULT 0, ' .
            "\"ip_hash\" $idType$after_key )"
        );
        if ($this->_type === 'oci') {
            $this->_db->exec(
                'declare
                    already_exists  exception;
                    columns_indexed exception;
                    pragma exception_init( already_exists, -955 );
                    pragma exception_init(columns_indexed, -1408);
                begin
                    execute immediate \'create index "user_session_expires" on "' . $this->_sanitizeIdentifier('user_session') . '" ("expires_at")\';
                exception
                    when already_exists or columns_indexed then
                    NULL;
                end;'
            );
        } else {
            $this->_db->exec(
                'CREATE INDEX "' .
                $this->_sanitizeIdentifier('user_session_expires') . '" ON "' .
                $this->_sanitizeIdentifier('user_session') . '" ("expires_at")'
            );
        }
    }

    /**
     * Migrate user table: add is_approved and email columns if missing
     *
     * @access private
     */
    private function _migrateUserTable()
    {
        $tableName    = $this->_sanitizeIdentifier('user');
        $usernameType = $this->_type === 'oci' ? 'VARCHAR2(255)' : 'VARCHAR(255)';

        // check which columns exist
        $columns = array();
        try {
            if ($this->_type === 'oci') {
                $rows = $this->_select(
                    'SELECT COLUMN_NAME FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ?',
                    array(strtoupper($tableName))
                );
            } else {
                $rows = $this->_select('PRAGMA table_info("' . $tableName . '")', array());
                if (!$rows) {
                    // MySQL / PostgreSQL
                    $rows = $this->_select(
                        'SELECT COLUMN_NAME as name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?',
                        array($tableName)
                    );
                }
            }
            if ($rows) {
                foreach ($rows as $row) {
                    $col = $row['COLUMN_NAME'] ?? $row['name'] ?? $row['column_name'] ?? '';
                    $columns[] = strtolower($col);
                }
            }
        } catch (\PDOException $e) {
            // if we can't check columns, try adding anyway
        }

        if (!in_array('is_approved', $columns)) {
            try {
                $this->_db->exec(
                    'ALTER TABLE "' . $tableName . '" ADD COLUMN "is_approved" INT NOT NULL DEFAULT 1'
                );
            } catch (\PDOException $e) {
                // column may already exist
            }
        }

        if (!in_array('email', $columns)) {
            try {
                $this->_db->exec(
                    'ALTER TABLE "' . $tableName . '" ADD COLUMN "email" ' . $usernameType . " DEFAULT ''"
                );
            } catch (\PDOException $e) {
                // column may already exist
            }
        }

        if (!in_array('force_password_change', $columns)) {
            try {
                $this->_db->exec(
                    'ALTER TABLE "' . $tableName . '" ADD COLUMN "force_password_change" INT NOT NULL DEFAULT 0'
                );
            } catch (\PDOException $e) {
                // column may already exist
            }
        }

        if (!in_array('reset_token', $columns)) {
            try {
                $this->_db->exec(
                    'ALTER TABLE "' . $tableName . '" ADD COLUMN "reset_token" ' . $usernameType . " DEFAULT ''"
                );
            } catch (\PDOException $e) {
                // column may already exist
            }
        }

        if (!in_array('reset_token_expires', $columns)) {
            try {
                $this->_db->exec(
                    'ALTER TABLE "' . $tableName . '" ADD COLUMN "reset_token_expires" INT NOT NULL DEFAULT 0'
                );
            } catch (\PDOException $e) {
                // column may already exist
            }
        }
    }

    /**
     * @access public
     * @param  string $username
     * @param  array  $userData
     * @return bool
     */
    public function createUser(string $username, array $userData): bool
    {
        try {
            return $this->_exec(
                'INSERT INTO "' . $this->_sanitizeIdentifier('user') .
                '" ("username","password_hash","role","is_active","is_approved","email","force_password_change","reset_token","reset_token_expires","created_at","last_login") VALUES(?,?,?,?,?,?,?,?,?,?,?)',
                array(
                    $username,
                    $userData['password_hash'],
                    $userData['role'] ?? 'user',
                    $userData['is_active'] ? 1 : 0,
                    ($userData['is_approved'] ?? true) ? 1 : 0,
                    $userData['email'] ?? '',
                    ($userData['force_password_change'] ?? false) ? 1 : 0,
                    $userData['reset_token'] ?? '',
                    $userData['reset_token_expires'] ?? 0,
                    $userData['created_at'] ?? time(),
                    $userData['last_login'] ?? 0,
                )
            );
        } catch (\PDOException $e) {
            error_log('Error creating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Read a user record
     *
     * @access public
     * @param  string $username
     * @return array|null
     */
    public function readUser(string $username): ?array
    {
        try {
            $row = $this->_select(
                'SELECT * FROM "' . $this->_sanitizeIdentifier('user') .
                '" WHERE "username" = ?',
                array($username),
                true
            );
            if ($row) {
                $row['is_active']             = (bool) $row['is_active'];
                $row['is_approved']           = isset($row['is_approved']) ? (bool) $row['is_approved'] : true;
                $row['email']                 = $row['email'] ?? '';
                $row['force_password_change'] = isset($row['force_password_change']) ? (bool) $row['force_password_change'] : false;
                $row['reset_token']           = $row['reset_token'] ?? '';
                $row['reset_token_expires']   = (int) ($row['reset_token_expires'] ?? 0);
                $row['created_at']            = (int) $row['created_at'];
                $row['last_login']            = (int) $row['last_login'];
                return $row;
            }
        } catch (\PDOException $e) {
            error_log('Error reading user: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Update a user record
     *
     * @access public
     * @param  string $username
     * @param  array  $userData
     * @return bool
     */
    public function updateUser(string $username, array $userData): bool
    {
        try {
            return $this->_exec(
                'UPDATE "' . $this->_sanitizeIdentifier('user') .
                '" SET "password_hash" = ?, "role" = ?, "is_active" = ?, "is_approved" = ?, "email" = ?, "force_password_change" = ?, "reset_token" = ?, "reset_token_expires" = ?, "last_login" = ? WHERE "username" = ?',
                array(
                    $userData['password_hash'],
                    $userData['role'],
                    $userData['is_active'] ? 1 : 0,
                    ($userData['is_approved'] ?? true) ? 1 : 0,
                    $userData['email'] ?? '',
                    ($userData['force_password_change'] ?? false) ? 1 : 0,
                    $userData['reset_token'] ?? '',
                    $userData['reset_token_expires'] ?? 0,
                    $userData['last_login'] ?? 0,
                    $username,
                )
            );
        } catch (\PDOException $e) {
            error_log('Error updating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a user record
     *
     * @access public
     * @param  string $username
     * @return bool
     */
    public function deleteUser(string $username): bool
    {
        try {
            // also delete user sessions
            $this->_exec(
                'DELETE FROM "' . $this->_sanitizeIdentifier('user_session') .
                '" WHERE "username" = ?',
                array($username)
            );
            return $this->_exec(
                'DELETE FROM "' . $this->_sanitizeIdentifier('user') .
                '" WHERE "username" = ?',
                array($username)
            );
        } catch (\PDOException $e) {
            error_log('Error deleting user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users
     *
     * @access public
     * @return array
     */
    public function listUsers(): array
    {
        try {
            $rows = $this->_select(
                'SELECT * FROM "' . $this->_sanitizeIdentifier('user') .
                '" ORDER BY "username"',
                array()
            );
            return array_map(function ($row) {
                $row['is_active']             = (bool) $row['is_active'];
                $row['is_approved']           = isset($row['is_approved']) ? (bool) $row['is_approved'] : true;
                $row['email']                 = $row['email'] ?? '';
                $row['force_password_change'] = isset($row['force_password_change']) ? (bool) $row['force_password_change'] : false;
                $row['reset_token']           = $row['reset_token'] ?? '';
                $row['reset_token_expires']   = (int) ($row['reset_token_expires'] ?? 0);
                $row['created_at']            = (int) $row['created_at'];
                $row['last_login']            = (int) $row['last_login'];
                return $row;
            }, $rows ?: array());
        } catch (\PDOException $e) {
            error_log('Error listing users: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Check if any users exist
     *
     * @access public
     * @return bool
     */
    public function hasUsers(): bool
    {
        try {
            $row = $this->_select(
                'SELECT COUNT(*) as "cnt" FROM "' . $this->_sanitizeIdentifier('user') . '"',
                array(),
                true
            );
            return $row && (int) $row['cnt'] > 0;
        } catch (\PDOException $e) {
            return false;
        }
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
        try {
            return $this->_exec(
                'INSERT INTO "' . $this->_sanitizeIdentifier('user_session') .
                '" ("id","username","created_at","expires_at","ip_hash") VALUES(?,?,?,?,?)',
                array(
                    $sessionId,
                    $sessionData['username'],
                    $sessionData['created_at'] ?? time(),
                    $sessionData['expires_at'],
                    $sessionData['ip_hash'] ?? '',
                )
            );
        } catch (\PDOException $e) {
            error_log('Error creating session: ' . $e->getMessage());
            return false;
        }
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
        try {
            $row = $this->_select(
                'SELECT * FROM "' . $this->_sanitizeIdentifier('user_session') .
                '" WHERE "id" = ?',
                array($sessionId),
                true
            );
            if ($row) {
                $row['created_at'] = (int) $row['created_at'];
                $row['expires_at'] = (int) $row['expires_at'];
                return $row;
            }
        } catch (\PDOException $e) {
            error_log('Error reading session: ' . $e->getMessage());
        }
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
        try {
            return $this->_exec(
                'DELETE FROM "' . $this->_sanitizeIdentifier('user_session') .
                '" WHERE "id" = ?',
                array($sessionId)
            );
        } catch (\PDOException $e) {
            error_log('Error deleting session: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Purge expired user sessions
     *
     * @access public
     * @return void
     */
    public function purgeExpiredSessions(): void
    {
        try {
            $this->_exec(
                'DELETE FROM "' . $this->_sanitizeIdentifier('user_session') .
                '" WHERE "expires_at" < ? AND "expires_at" != 0',
                array(time())
            );
        } catch (\PDOException $e) {
            error_log('Error purging expired sessions: ' . $e->getMessage());
        }
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
     * check if the current database type supports dropping columns
     *
     * @access private
     * @return bool
     */
    private function _supportsDropColumn()
    {
        $supportsDropColumn = true;
        if ($this->_type === 'sqlite') {
            try {
                $row                = $this->_select('SELECT sqlite_version() AS "v"', array(), true);
                $supportsDropColumn = (bool) version_compare($row['v'], '3.35.0', '>=');
            } catch (PDOException $e) {
                $supportsDropColumn = false;
            }
        }
        return $supportsDropColumn;
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
            if ($this->_supportsDropColumn()) {
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                    '" DROP COLUMN "postdate"'
                );
            }
        }
        if (version_compare($oldversion, '1.7.8', '<=')) {
            if ($this->_supportsDropColumn()) {
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                    '" DROP COLUMN "opendiscussion"'
                );
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                    '" DROP COLUMN "burnafterreading"'
                );
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                    '" DROP COLUMN "attachment"'
                );
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('paste') .
                    '" DROP COLUMN "attachmentname"'
                );
                $this->_db->exec(
                    'ALTER TABLE "' . $this->_sanitizeIdentifier('comment') .
                    '" DROP COLUMN "nickname"'
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
