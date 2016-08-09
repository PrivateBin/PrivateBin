<?php

use PrivateBin\Data\Database;

class DatabaseTest extends PHPUnit_Framework_TestCase
{
    private $_model;

    private $_path;

    private $_options = array(
        'dsn' => 'sqlite::memory:',
        'usr' => null,
        'pwd' => null,
        'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
    );

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        $this->_model = Database::getInstance($this->_options);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        if (is_dir($this->_path)) {
            Helper::rmDir($this->_path);
        }
    }

    public function testDatabaseBasedDataStoreWorks()
    {
        $this->_model->delete(Helper::getPasteId());

        // storing pastes
        $paste = Helper::getPaste(array('expire_date' => 1344803344));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode($paste)), $this->_model->read(Helper::getPasteId()));

        // storing comments
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does not yet exist');
        $this->assertTrue($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), Helper::getComment()) !== false, 'store comment');
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment exists after storing it');
        $comment = json_decode(json_encode(Helper::getComment()));
        $comment->id = Helper::getCommentId();
        $comment->parentid = Helper::getPasteId();
        $this->assertEquals(
            array($comment->meta->postdate => $comment),
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
        $this->_model->delete(Helper::getPasteId());
        $original = $paste = Helper::getPasteWithAttachment(array('expire_date' => 1344803344));
        $paste['meta']['burnafterreading'] = $original['meta']['burnafterreading'] = true;
        $paste['meta']['attachment'] = $paste['attachment'];
        $paste['meta']['attachmentname'] = $paste['attachmentname'];
        unset($paste['attachment'], $paste['attachmentname']);
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode($original)), $this->_model->read(Helper::getPasteId()));
    }

    public function testPurge()
    {
        $this->_model->delete(Helper::getPasteId());
        $expired = Helper::getPaste(array('expire_date' => 1344803344));
        $paste = Helper::getPaste(array('expire_date' => time() + 3600));
        $keys = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'x', 'y', 'z');
        $ids = array();
        foreach ($keys as $key) {
            $ids[$key] = substr(md5($key), 0, 16);
            $this->_model->delete($ids[$key]);
            $this->assertFalse($this->_model->exists($ids[$key]), "paste $key does not yet exist");
            if (in_array($key, array('x', 'y', 'z'))) {
                $this->assertTrue($this->_model->create($ids[$key], $paste), "store $key paste");
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

    /**
     * @expectedException PDOException
     */
    public function testGetIbmInstance()
    {
        Database::getInstance(array(
            'dsn' => 'ibm:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetInformixInstance()
    {
        Database::getInstance(array(
            'dsn' => 'informix:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetMssqlInstance()
    {
        Database::getInstance(array(
            'dsn' => 'mssql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetMysqlInstance()
    {
        Database::getInstance(array(
            'dsn' => 'mysql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetOciInstance()
    {
        Database::getInstance(array(
            'dsn' => 'oci:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetPgsqlInstance()
    {
        Database::getInstance(array(
            'dsn' => 'pgsql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 5
     */
    public function testGetFooInstance()
    {
        Database::getInstance(array(
            'dsn' => 'foo:', 'usr' => null, 'pwd' => null, 'opt' => null
        ));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 6
     */
    public function testMissingDsn()
    {
        $options = $this->_options;
        unset($options['dsn']);
        Database::getInstance($options);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 6
     */
    public function testMissingUsr()
    {
        $options = $this->_options;
        unset($options['usr']);
        Database::getInstance($options);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 6
     */
    public function testMissingPwd()
    {
        $options = $this->_options;
        unset($options['pwd']);
        Database::getInstance($options);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 6
     */
    public function testMissingOpt()
    {
        $options = $this->_options;
        unset($options['opt']);
        Database::getInstance($options);
    }

    public function testOldAttachments()
    {
        mkdir($this->_path);
        $path = $this->_path . DIRECTORY_SEPARATOR . 'attachement-test.sq3';
        if (is_file($path)) unlink($path);
        $this->_options['dsn'] = 'sqlite:' . $path;
        $this->_options['tbl'] = 'bar_';
        $model = Database::getInstance($this->_options);

        $original = $paste = Helper::getPasteWithAttachment(array('expire_date' => 1344803344));
        $paste['meta']['attachment'] = $paste['attachment'];
        $paste['meta']['attachmentname'] = $paste['attachmentname'];
        unset($paste['attachment'], $paste['attachmentname']);
        $meta = $paste['meta'];

        $db = new PDO(
            $this->_options['dsn'],
            $this->_options['usr'],
            $this->_options['pwd'],
            $this->_options['opt']
        );
        $statement = $db->prepare('INSERT INTO bar_paste VALUES(?,?,?,?,?,?,?,?,?)');
        $statement->execute(
            array(
                Helper::getPasteId(),
                $paste['data'],
                $paste['meta']['postdate'],
                1344803344,
                0,
                0,
                json_encode($meta),
                null,
                null,
            )
        );
        $statement->closeCursor();

        $this->assertTrue($model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertEquals(json_decode(json_encode($original)), $model->read(Helper::getPasteId()));

        Helper::rmDir($this->_path);
    }

    public function testTableUpgrade()
    {
        mkdir($this->_path);
        $path = $this->_path . DIRECTORY_SEPARATOR . 'db-test.sq3';
        if (is_file($path)) unlink($path);
        $this->_options['dsn'] = 'sqlite:' . $path;
        $this->_options['tbl'] = 'foo_';
        $db = new PDO(
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
            "dataid CHAR(16) NOT NULL, " .
            'pasteid CHAR(16), ' .
            'parentid CHAR(16), ' .
            'data BLOB, ' .
            'nickname BLOB, ' .
            'vizhash BLOB, ' .
            "postdate INT );"
        );
        $this->assertInstanceOf(Database::class, Database::getInstance($this->_options));
        Helper::rmDir($this->_path);
    }
}
