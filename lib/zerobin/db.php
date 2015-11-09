<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.22
 */

/**
 * zerobin_db
 *
 * Model for DB access, implemented as a singleton.
 */
class zerobin_db extends zerobin_abstract
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
     * @return zerobin_db
     */
    public static function getInstance($options = null)
    {
        // if needed initialize the singleton
        if(!(self::$_instance instanceof zerobin_db)) {
            self::$_instance = new self;
        }

        if (is_array($options))
        {
            // set table prefix if given
            if (array_key_exists('tbl', $options)) self::$_prefix = $options['tbl'];

            // initialize the db connection with new options
            if (
                array_key_exists('dsn', $options) &&
                array_key_exists('usr', $options) &&
                array_key_exists('pwd', $options) &&
                array_key_exists('opt', $options)
            )
            {
                // set default options
                $options['opt'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
                $options['opt'][PDO::ATTR_EMULATE_PREPARES] = false;
                $options['opt'][PDO::ATTR_PERSISTENT] = true;
                $db_tables_exist = true;

                // setup type and dabase connection
                self::$_type = strtolower(
                    substr($options['dsn'], 0, strpos($options['dsn'], ':'))
                );
                $tableQuery = self::_getTableQuery(self::$_type);
                self::$_db = new PDO(
                    $options['dsn'],
                    $options['usr'],
                    $options['pwd'],
                    $options['opt']
                );

                // check if the database contains the required tables
                $tables = self::$_db->query($tableQuery)->fetchAll(PDO::FETCH_COLUMN, 0);

                // create paste table if necessary
                if (!in_array(self::$_prefix . 'paste', $tables))
                {
                    self::_createPasteTable();
                    $db_tables_exist = false;
                }

                // create comment table if necessary
                if (!in_array(self::$_prefix . 'comment', $tables))
                {
                    self::_createCommentTable();
                    $db_tables_exist = false;
                }

                // create config table if necessary
                $db_version = zerobin::VERSION;
                if (!in_array(self::$_prefix . 'config', $tables))
                {
                    self::_createConfigTable();
                    // if we only needed to create the config table, the DB is older then 0.22
                    if ($db_tables_exist) $db_version = '0.21';
                }
                else
                {
                    $db_version = self::_getConfig('VERSION');
                }

                // update database structure if necessary
                if (version_compare($db_version, zerobin::VERSION, '<'))
                {
                    self::_upgradeDatabase($db_version);
                }
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
            if(false !== self::$_cache[$pasteid]) {
                return false;
            } else {
                unset(self::$_cache[$pasteid]);
            }
        }

        $opendiscussion = $burnafterreading = false;
        $attachment = $attachmentname = '';
        $meta = $paste['meta'];
        unset($meta['postdate']);
        $expire_date = 0;
        if (array_key_exists('expire_date', $paste['meta']))
        {
            $expire_date = (int) $paste['meta']['expire_date'];
            unset($meta['expire_date']);
        }
        if (array_key_exists('opendiscussion', $paste['meta']))
        {
            $opendiscussion = (bool) $paste['meta']['opendiscussion'];
            unset($meta['opendiscussion']);
        }
        if (array_key_exists('burnafterreading', $paste['meta']))
        {
            $burnafterreading = (bool) $paste['meta']['burnafterreading'];
            unset($meta['burnafterreading']);
        }
        if (array_key_exists('attachment', $paste['meta']))
        {
            $attachment = $paste['meta']['attachment'];
            unset($meta['attachment']);
        }
        if (array_key_exists('attachmentname', $paste['meta']))
        {
            $attachmentname = $paste['meta']['attachmentname'];
            unset($meta['attachmentname']);
        }
        return self::_exec(
            'INSERT INTO ' . self::$_prefix . 'paste VALUES(?,?,?,?,?,?,?,?,?)',
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
            $paste = self::_select(
                'SELECT * FROM ' . self::$_prefix . 'paste WHERE dataid = ?',
                array($pasteid), true
            );

            if(false !== $paste) {
                // create object
                self::$_cache[$pasteid] = new stdClass;
                self::$_cache[$pasteid]->data = $paste['data'];

                $meta = json_decode($paste['meta']);
                if (!is_object($meta)) $meta = new stdClass;

                // support older attachments
                if (property_exists($meta, 'attachment'))
                {
                    self::$_cache[$pasteid]->attachment = $meta->attachment;
                    unset($meta->attachment);
                    if (property_exists($meta, 'attachmentname'))
                    {
                        self::$_cache[$pasteid]->attachmentname = $meta->attachmentname;
                        unset($meta->attachmentname);
                    }
                }
                // support current attachments
                elseif (array_key_exists('attachment', $paste) && strlen($paste['attachment']))
                {
                    self::$_cache[$pasteid]->attachment = $paste['attachment'];
                    if (array_key_exists('attachmentname', $paste) && strlen($paste['attachmentname']))
                    {
                        self::$_cache[$pasteid]->attachmentname = $paste['attachmentname'];
                    }
                }
                self::$_cache[$pasteid]->meta = $meta;
                self::$_cache[$pasteid]->meta->postdate = (int) $paste['postdate'];
                $expire_date = (int) $paste['expiredate'];
                if (
                    $expire_date > 0
                ) self::$_cache[$pasteid]->meta->expire_date = $expire_date;
                if (
                    $paste['opendiscussion']
                ) self::$_cache[$pasteid]->meta->opendiscussion = true;
                if (
                    $paste['burnafterreading']
                ) self::$_cache[$pasteid]->meta->burnafterreading = true;
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
            'DELETE FROM ' . self::$_prefix . 'paste WHERE dataid = ?',
            array($pasteid)
        );
        self::_exec(
            'DELETE FROM ' . self::$_prefix . 'comment WHERE pasteid = ?',
            array($pasteid)
        );
        if (
            array_key_exists($pasteid, self::$_cache)
        ) unset(self::$_cache[$pasteid]);
    }

    /**
     * Test if a paste exists.
     *
     * @access public
     * @param  string $dataid
     * @return void
     */
    public function exists($pasteid)
    {
        if (
            !array_key_exists($pasteid, self::$_cache)
        ) self::$_cache[$pasteid] = $this->read($pasteid);
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
     * @return int|false
     */
    public function createComment($pasteid, $parentid, $commentid, $comment)
    {
        return self::_exec(
            'INSERT INTO ' . self::$_prefix . 'comment VALUES(?,?,?,?,?,?,?)',
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
            'SELECT * FROM ' . self::$_prefix . 'comment WHERE pasteid = ?',
            array($pasteid)
        );

        // create comment list
        $comments = array();
        if (count($rows))
        {
            foreach ($rows as $row)
            {
                $i = $this->getOpenSlot($comments, (int) $row['postdate']);
                $comments[$i] = new stdClass;
                $comments[$i]->id = $row['dataid'];
                $comments[$i]->parentid = $row['parentid'];
                $comments[$i]->data = $row['data'];
                $comments[$i]->meta = new stdClass;
                $comments[$i]->meta->postdate = (int) $row['postdate'];
                if (array_key_exists('nickname', $row))
                    $comments[$i]->meta->nickname = $row['nickname'];
                if (array_key_exists('vizhash', $row))
                    $comments[$i]->meta->vizhash = $row['vizhash'];
            }
            ksort($comments);
        }
        return $comments;
    }

    /**
     * Test if a comment exists.
     *
     * @access public
     * @param  string $dataid
     * @param  string $parentid
     * @param  string $commentid
     * @return void
     */
    public function existsComment($pasteid, $parentid, $commentid)
    {
        return (bool) self::_select(
            'SELECT dataid FROM ' . self::$_prefix . 'comment ' .
            'WHERE pasteid = ? AND parentid = ? AND dataid = ?',
            array($pasteid, $parentid, $commentid), true
        );
    }

    /**
     * execute a statement
     *
     * @access private
     * @static
     * @param  string $sql
     * @param  array $params
     * @throws PDOException
     * @return array
     */
    private static function _exec($sql, array $params)
    {
        $statement = self::$_db->prepare($sql);
        $result = $statement->execute($params);
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
        switch($type)
        {
            case 'ibm':
                $sql = 'SELECT tabname FROM SYSCAT.TABLES ';
                break;
            case 'informix':
                $sql = 'SELECT tabname FROM systables ';
                break;
            case 'mssql':
                $sql = "SELECT name FROM sysobjects "
                     . "WHERE type = 'U' ORDER BY name";
                break;
            case 'mysql':
                $sql = 'SHOW TABLES';
                break;
            case 'oci':
                $sql = 'SELECT table_name FROM all_tables';
                break;
            case 'pgsql':
                $sql = "SELECT c.relname AS table_name "
                     . "FROM pg_class c, pg_user u "
                     . "WHERE c.relowner = u.usesysid AND c.relkind = 'r' "
                     . "AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname) "
                     . "AND c.relname !~ '^(pg_|sql_)' "
                     . "UNION "
                     . "SELECT c.relname AS table_name "
                     . "FROM pg_class c "
                     . "WHERE c.relkind = 'r' "
                     . "AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname) "
                     . "AND NOT EXISTS (SELECT 1 FROM pg_user WHERE usesysid = c.relowner) "
                     . "AND c.relname !~ '^pg_'";
                break;
            case 'sqlite':
                $sql = "SELECT name FROM sqlite_master WHERE type='table' "
                     . "UNION ALL SELECT name FROM sqlite_temp_master "
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
            'SELECT value FROM ' . self::$_prefix . 'config WHERE id = ?',
            array($key), true
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
        if (self::$_type === 'mysql')
        {
            $after_key = ", PRIMARY KEY ($key)";
        }
        else
        {
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
        self::$_db->exec(
            'CREATE TABLE ' . self::$_prefix . 'paste ( ' .
            "dataid CHAR(16) NOT NULL$main_key, " .
            'data BLOB, ' .
            'postdate INT, ' .
            'expiredate INT, ' .
            'opendiscussion INT, ' .
            'burnafterreading INT, ' .
            'meta TEXT, ' .
            'attachment MEDIUMBLOB, ' .
            "attachmentname BLOB$after_key );"
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
        self::$_db->exec(
            'CREATE TABLE ' . self::$_prefix . 'comment ( ' .
            "dataid CHAR(16) NOT NULL$main_key, " .
            'pasteid CHAR(16), ' .
            'parentid CHAR(16), ' .
            'data BLOB, ' .
            'nickname BLOB, ' .
            'vizhash BLOB, ' .
            "postdate INT$after_key );"
        );
        self::$_db->exec(
            'CREATE INDEX parent ON ' . self::$_prefix . 'comment(pasteid);'
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
            'CREATE TABLE ' . self::$_prefix . 'config ( ' .
            "id CHAR(16) NOT NULL$main_key, value TEXT$after_key );"
        );
        self::_exec(
            'INSERT INTO ' . self::$_prefix . 'config VALUES(?,?)',
            array('VERSION', zerobin::VERSION)
        );
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
        switch ($oldversion)
        {
            case '0.21':
                // create the meta column if necessary (pre 0.21 change)
                try {
                    self::$_db->exec('SELECT meta FROM ' . self::$_prefix . 'paste LIMIT 1;', array());
                } catch (PDOException $e) {
                    self::$_db->exec('ALTER TABLE ' . self::$_prefix . 'paste ADD COLUMN meta TEXT;');
                }
                // SQLite only allows one ALTER statement at a time...
                self::$_db->exec(
                    'ALTER TABLE ' . self::$_prefix . 'paste ADD COLUMN attachment MEDIUMBLOB;'
                );
                self::$_db->exec(
                    'ALTER TABLE ' . self::$_prefix . 'paste ADD COLUMN attachmentname BLOB;'
                );
                // SQLite doesn't support MODIFY, but it allows TEXT of similar
                // size as BLOB, so there is no need to change it there
                if (self::$_type !== 'sqlite')
                {
                    self::$_db->exec(
                        'ALTER TABLE ' . self::$_prefix . 'paste ' .
                        'ADD PRIMARY KEY (dataid),' .
                        'MODIFY COLUMN data BLOB;'
                    );
                    self::$_db->exec(
                        'ALTER TABLE ' . self::$_prefix . 'comment ' .
                        'ADD PRIMARY KEY (dataid),' .
                        'MODIFY COLUMN data BLOB, ' .
                        'MODIFY COLUMN nickname BLOB, ' .
                        'MODIFY COLUMN vizhash BLOB;'
                    );
                }
                else
                {
                    self::$_db->exec(
                        'CREATE UNIQUE INDEX primary ON ' . self::$_prefix . 'paste(dataid);'
                    );
                    self::$_db->exec(
                        'CREATE UNIQUE INDEX primary ON ' . self::$_prefix . 'comment(dataid);'
                    );
                }
                self::$_db->exec(
                    'CREATE INDEX parent ON ' . self::$_prefix . 'comment(pasteid);'
                );
        }
    }
}
