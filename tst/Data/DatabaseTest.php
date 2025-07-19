<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Controller;
use PrivateBin\Data\Database;
use PrivateBin\Data\Filesystem;
use PrivateBin\Persistence\ServerSalt;

class DatabaseTest extends TestCase
{
    private $_model;

    private $_path;

    private $_options = array(
        'dsn' => 'sqlite::memory:',
        'usr' => null,
        'pwd' => null,
        'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
    );

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        $this->_model = new Database($this->_options);
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        if (is_dir($this->_path)) {
            Helper::rmDir($this->_path);
        }
    }

    public function testSaltMigration()
    {
        ServerSalt::setStore(new Filesystem(array('dir' => 'data')));
        $salt = ServerSalt::get();
        $file = 'data' . DIRECTORY_SEPARATOR . 'salt.php';
        $this->assertFileExists($file, 'ServerSalt got initialized and stored on disk');
        $this->assertNotEquals($salt, '');
        ServerSalt::setStore($this->_model);
        ServerSalt::get();
        $this->assertFileDoesNotExist($file, 'legacy ServerSalt got removed');
        $this->assertEquals($salt, ServerSalt::get(), 'ServerSalt got preserved & migrated');
    }

    public function testDatabaseBasedDataStoreWorks()
    {
        $error_log_setting = ini_get('error_log');
        $this->_model->delete(Helper::getPasteId());

        // storing pastes
        $paste = Helper::getPaste();
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        ini_set('error_log', '/dev/null');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        ini_set('error_log', $error_log_setting);
        $this->assertEquals($paste, $this->_model->read(Helper::getPasteId()));

        // storing comments
        $comment1             = Helper::getComment();
        $comment2             = Helper::getComment();
        $meta1                = $comment1['meta'];
        $meta2                = $comment2['meta'];
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment 1 does not yet exist');
        $this->assertTrue($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), $comment1) !== false, 'store comment 1');
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment 2 exists after storing it');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getPasteId()), 'comment 2 does not yet exist');
        $this->assertTrue($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getPasteId(), $comment2) !== false, 'store comment 2');
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getPasteId()), 'comment 2 exists after storing it');
        $comment1['id']       = Helper::getCommentId();
        $comment1['parentid'] = Helper::getPasteId();
        $comment1['meta']     = $meta1;
        $comment2['id']       = Helper::getPasteId();
        $comment2['parentid'] = Helper::getPasteId();
        $comment2['meta']     = $meta2;
        $this->assertEquals(
            array(
                $comment1['meta']['created']        => $comment1,
                $comment2['meta']['created'] . '.1' => $comment2,
            ),
            $this->_model->readComments(Helper::getPasteId())
        );

        // deleting pastes
        $this->_model->delete(Helper::getPasteId());
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment was deleted with paste');
        $this->assertFalse($this->_model->read(Helper::getPasteId()), 'paste can no longer be found');
    }

    public function testDatabaseBasedAttachmentStoreWorks()
    {
        $error_log_setting = ini_get('error_log');
        $this->_model->delete(Helper::getPasteId());
        $original                          = $paste                                = Helper::getPaste(array('expire_date' => 1344803344));
        $paste['meta']['burnafterreading'] = $original['meta']['burnafterreading'] = true;
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        ini_set('error_log', '/dev/null');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        ini_set('error_log', $error_log_setting);
        $this->assertEquals($original, $this->_model->read(Helper::getPasteId()));
    }

    /**
     * pastes a-g are expired and should get deleted, x never expires and y-z expire in an hour
     */
    public function testPurge()
    {
        $this->_model->delete(Helper::getPasteId());
        $expired = Helper::getPaste(array('expire_date' => 1344803344));
        $paste   = Helper::getPaste(array('expire_date' => time() + 3600));
        $keys    = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'x', 'y', 'z');
        $ids     = array();
        foreach ($keys as $key) {
            $ids[$key] = hash('fnv164', $key);
            $this->_model->delete($ids[$key]);
            $this->assertFalse($this->_model->exists($ids[$key]), "paste $key does not yet exist");
            if (in_array($key, array('y', 'z'))) {
                $this->assertTrue($this->_model->create($ids[$key], $paste), "store $key paste");
            } elseif ($key === 'x') {
                $data = Helper::getPaste();
                $this->assertTrue($this->_model->create($ids[$key], $data), "store $key paste");
            } else {
                $this->assertTrue($this->_model->create($ids[$key], $expired), "store $key paste");
            }
            $this->assertTrue($this->_model->exists($ids[$key]), "paste $key exists after storing it");
        }
        $this->_model->purge(10);
        foreach ($ids as $key => $id) {
            if (in_array($key, array('x', 'y', 'z'))) {
                $this->assertTrue($this->_model->exists($id), "paste $key exists after purge");
                $this->_model->delete($id);
            } else {
                $this->assertFalse($this->_model->exists($id), "paste $key was purged");
            }
        }
    }

    public function testErrorDetection()
    {
        $error_log_setting = ini_get('error_log');
        $this->_model->delete(Helper::getPasteId());
        $paste = Helper::getPaste(array('expire' => "Invalid UTF-8 sequence: \xB1\x31"));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        ini_set('error_log', '/dev/null');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store broken paste');
        ini_set('error_log', $error_log_setting);
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does still not exist');
    }

    public function testCommentErrorDetection()
    {
        $error_log_setting = ini_get('error_log');
        $this->_model->delete(Helper::getPasteId());
        $data    = Helper::getPaste();
        $comment = Helper::getComment(array('icon' => "Invalid UTF-8 sequence: \xB1\x31"));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $data), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does not yet exist');
        ini_set('error_log', '/dev/null');
        $this->assertFalse($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), $comment), 'unable to store broken comment');
        ini_set('error_log', $error_log_setting);
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does still not exist');
    }

    public function testGetIbmInstance()
    {
        $this->expectException(PDOException::class);
        new Database(array(
            'dsn' => 'ibm:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        ));
    }

    public function testGetInformixInstance()
    {
        $this->expectException(PDOException::class);
        new Database(array(
            'dsn' => 'informix:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        ));
    }

    public function testGetMssqlInstance()
    {
        $this->expectException(PDOException::class);
        new Database(array(
            'dsn' => 'mssql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        ));
    }

    public function testGetMysqlInstance()
    {
        $this->expectException(PDOException::class);
        new Database(array(
            'dsn' => 'mysql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        ));
    }

    public function testGetOciInstance()
    {
        $this->expectException(PDOException::class);
        new Database(array(
            'dsn' => 'oci:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        ));
    }

    public function testGetPgsqlInstance()
    {
        $this->expectException(PDOException::class);
        new Database(array(
            'dsn' => 'pgsql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        ));
    }

    public function testGetFooInstance()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(5);
        new Database(array(
            'dsn' => 'foo:', 'usr' => null, 'pwd' => null, 'opt' => null,
        ));
    }

    public function testMissingDsn()
    {
        $options = $this->_options;
        unset($options['dsn']);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(6);
        new Database($options);
    }

    public function testMissingUsr()
    {
        $options = $this->_options;
        unset($options['usr']);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(6);
        new Database($options);
    }

    public function testMissingPwd()
    {
        $options = $this->_options;
        unset($options['pwd']);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(6);
        new Database($options);
    }

    public function testMissingOpt()
    {
        $options = $this->_options;
        unset($options['opt']);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(6);
        new Database($options);
    }

    public function testTableUpgrade()
    {
        mkdir($this->_path);
        $path = $this->_path . DIRECTORY_SEPARATOR . 'db-test.sq3';
        if (is_file($path)) {
            unlink($path);
        }
        $this->_options['dsn'] = 'sqlite:' . $path;
        $this->_options['tbl'] = 'foo_';
        $db                    = new PDO(
            $this->_options['dsn'],
            $this->_options['usr'],
            $this->_options['pwd'],
            $this->_options['opt']
        );
        $db->exec(
            'CREATE TABLE foo_paste ( ' .
            'dataid CHAR(16), ' .
            'data TEXT, ' .
            'postdate INT, ' .
            'expiredate INT, ' .
            'opendiscussion INT, ' .
            'burnafterreading INT );'
        );
        $db->exec(
            'CREATE TABLE foo_comment ( ' .
            'dataid CHAR(16) NOT NULL, ' .
            'pasteid CHAR(16), ' .
            'parentid CHAR(16), ' .
            'data BLOB, ' .
            'nickname BLOB, ' .
            'vizhash BLOB, ' .
            'postdate INT );'
        );
        $this->assertInstanceOf('PrivateBin\\Data\\Database', new Database($this->_options));

        // check if version number was upgraded in created configuration table
        $statement = $db->prepare('SELECT value FROM foo_config WHERE id LIKE ?');
        $statement->execute(array('VERSION'));
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        $this->assertEquals(Controller::VERSION, $result['value']);
        Helper::rmDir($this->_path);
    }

    public function testOciClob()
    {
        $int    = (int) random_bytes(1);
        $string = random_bytes(10);
        $clob   = fopen('php://memory', 'r+');
        fwrite($clob, $string);
        rewind($clob);
        $this->assertEquals($int, Database::_sanitizeClob($int));
        $this->assertEquals($string, Database::_sanitizeClob($string));
        $this->assertEquals($string, Database::_sanitizeClob($clob));
    }
}
