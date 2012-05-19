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
 * zerobin_db
 *
 * Model for DB access, implemented as a singleton.
 */
class zerobin_db extends zerobin_abstract
{
    /*
     * @access private
     * @static
     * @var array to cache select queries
     */
    private static $_cache = array();

    /*
     * @access private
     * @static
     * @var PDO instance of database connection
     */
    private static $_db;

    /*
     * @access private
     * @static
     * @var string table prefix
     */
    private static $_prefix = '';

    /*
     * @access private
     * @static
     * @var string database type
     */
    private static $_type = '';

    /**
     * get instance of singleton
     *
     * @access public
     * @static
     * @throws Exception
     * @return zerobin_db
     */
    public static function getInstance($options = null)
    {
        // if needed initialize the singleton
        if(null === self::$_instance) {
            parent::$_instance = new self;
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
                self::$_db = new PDO(
                    $options['dsn'],
                    $options['usr'],
                    $options['pwd'],
                    $options['opt']
                );

                // check if the database contains the required tables
                self::$_type = strtolower(
                    substr($options['dsn'], 0, strpos($options['dsn'], ':'))
                );
                switch(self::$_type)
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
                            'PDO type ' .
                            self::$_type .
                            ' is currently not supported.'
                        );
                }
                $statement = self::$_db->query($sql);
                $tables = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

                // create paste table if needed
                if (!array_key_exists(self::$_prefix . 'paste', $tables))
                {
                    self::$_db->exec(
                        'CREATE TABLE ' . self::$_prefix . 'paste ( ' .
                        'dataid CHAR(16), ' .
                        'data TEXT, ' .
                        'postdate INT, ' .
                        'expiredate INT, ' .
                        'opendiscussion INT, ' .
                        'burnafterreading INT );'
                    );
                }

                // create comment table if needed
                if (!array_key_exists(self::$_prefix . 'comment', $tables))
                {
                    self::$_db->exec(
                        'CREATE TABLE ' . self::$_prefix . 'comment ( ' .
                        'dataid CHAR(16), ' .
                        'pasteid CHAR(16), ' .
                        'parentid CHAR(16), ' .
                        'data TEXT, ' .
                        'nickname VARCHAR(255), ' .
                        'vizhash TEXT, ' .
                        'postdate INT );'
                    );
                }
            }
        }

        return parent::$_instance;
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
            !array_key_exists('opendiscussion', $paste['meta'])
        ) $paste['meta']['opendiscussion'] = false;
        if (
            !array_key_exists('burnafterreading', $paste['meta'])
        ) $paste['meta']['burnafterreading'] = false;
        return self::_exec(
            'INSERT INTO ' . self::$_prefix . 'paste VALUES(?,?,?,?,?,?)',
            array(
                $pasteid,
                $paste['data'],
                $paste['meta']['postdate'],
                $paste['meta']['expire_date'],
                (int) $paste['meta']['opendiscussion'],
                (int) $paste['meta']['burnafterreading'],
            )
        );
    }

    /**
     * Read a paste.
     *
     * @access public
     * @param  string $pasteid
     * @return string
     */
    public function read($pasteid)
    {
        if (
            !array_key_exists($pasteid, self::$_cache)
        ) self::$_cache[$pasteid] = self::_select(
            'SELECT * FROM ' . self::$_prefix . 'paste WHERE dataid = ?',
            array($pasteid), true
        );

        // create object
        $paste = new stdClass;
        $paste->data = self::$_cache[$pasteid]['data'];
        $paste->meta = new stdClass;
        $paste->meta->postdate = (int) self::$_cache[$pasteid]['postdate'];
        $paste->meta->expire_date = (int) self::$_cache[$pasteid]['expiredate'];
        if (
            self::$_cache[$pasteid]['opendiscussion']
        ) $paste->meta->opendiscussion = true;
        if (
            self::$_cache[$pasteid]['burnafterreading']
        ) $paste->meta->burnafterreading = true;

        return $paste;
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
        ) self::$_cache[$pasteid] = self::_select(
            'SELECT * FROM ' . self::$_prefix . 'paste WHERE dataid = ?',
            array($pasteid), true
        );
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
                $pasteid,
                $parentid,
                $commentid,
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

        // create object
        $commentTemplate = new stdClass;
        $commentTemplate->meta = new stdClass;

        // create comment list
        $comments = array();
        if (count($rows))
        {
            foreach ($rows as $row)
            {
                $i = (int) $row['postdate'];
                $comments[$i] = clone $commentTemplate;
                $comments[$i]->data = $row['data'];
                $comments[$i]->meta->nickname = $row['nickname'];
                $comments[$i]->meta->vizhash = $row['vizhash'];
                $comments[$i]->meta->postdate = $i;
                $comments[$i]->meta->commentid = $row['dataid'];
                $comments[$i]->meta->parentid = $row['parentid'];
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
}
