<?php
class privatebin_dbTest extends PHPUnit_Framework_TestCase
{
    private $_model;

    private $_options = array(
        'dsn' => 'sqlite::memory:',
        'usr' => null,
        'pwd' => null,
        'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
    );

    public function setUp()
    {
        /* Setup Routine */
        $this->_model = privatebin_db::getInstance($this->_options);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        if (is_dir(PATH . 'data')) helper::rmdir(PATH . 'data');
    }

    public function testDatabaseBasedDataStoreWorks()
    {
        $this->_model->delete(helper::getPasteId());

        // storing pastes
        $paste = helper::getPaste(array('expire_date' => 1344803344));
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode($paste)), $this->_model->read(helper::getPasteId()));

        // storing comments
        $this->assertFalse($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'comment does not yet exist');
        $this->assertTrue($this->_model->createComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId(), helper::getComment()) !== false, 'store comment');
        $this->assertTrue($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'comment exists after storing it');
        $comment = json_decode(json_encode(helper::getComment()));
        $comment->id = helper::getCommentId();
        $comment->parentid = helper::getPasteId();
        $this->assertEquals(
            array($comment->meta->postdate => $comment),
            $this->_model->readComments(helper::getPasteId())
        );

        // deleting pastes
        $this->_model->delete(helper::getPasteId());
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste successfully deleted');
        $this->assertFalse($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'comment was deleted with paste');
        $this->assertFalse($this->_model->read(helper::getPasteId()), 'paste can no longer be found');
    }

    public function testDatabaseBasedAttachmentStoreWorks()
    {
        $this->_model->delete(helper::getPasteId());
        $original = $paste = helper::getPasteWithAttachment(array('expire_date' => 1344803344));
        $paste['meta']['burnafterreading'] = $original['meta']['burnafterreading'] = true;
        $paste['meta']['attachment'] = $paste['attachment'];
        $paste['meta']['attachmentname'] = $paste['attachmentname'];
        unset($paste['attachment'], $paste['attachmentname']);
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals(json_decode(json_encode($original)), $this->_model->read(helper::getPasteId()));
    }

    public function testPurge()
    {
        $this->_model->delete(helper::getPasteId());
        $expired = helper::getPaste(array('expire_date' => 1344803344));
        $paste = helper::getPaste(array('expire_date' => time() + 3600));
        $keys = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'x', 'y', 'z');
        $ids = array();
        foreach ($keys as $key)
        {
            $ids[$key] = substr(md5($key), 0, 16);
            $this->assertFalse($this->_model->exists($ids[$key]), "paste $key does not yet exist");
            if (in_array($key, array('x', 'y', 'z')))
            {
                $this->assertTrue($this->_model->create($ids[$key], $paste), "store $key paste");
            }
            else
            {
                $this->assertTrue($this->_model->create($ids[$key], $expired), "store $key paste");
            }
            $this->assertTrue($this->_model->exists($ids[$key]), "paste $key exists after storing it");
        }
        $this->_model->purge(10);
        foreach ($ids as $key => $id)
        {
            if (in_array($key, array('x', 'y', 'z')))
            {
                $this->assertTrue($this->_model->exists($ids[$key]), "paste $key exists after purge");
            }
            else
            {
                $this->assertFalse($this->_model->exists($ids[$key]), "paste $key was purged");
            }
        }
    }

    /**
     * @expectedException PDOException
     */
    public function testGetIbmInstance()
    {
        privatebin_db::getInstance(array(
            'dsn' => 'ibm:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetInformixInstance()
    {
        privatebin_db::getInstance(array(
            'dsn' => 'informix:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetMssqlInstance()
    {
        privatebin_db::getInstance(array(
            'dsn' => 'mssql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetMysqlInstance()
    {
        privatebin_db::getInstance(array(
            'dsn' => 'mysql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetOciInstance()
    {
        privatebin_db::getInstance(array(
            'dsn' => 'oci:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetPgsqlInstance()
    {
        privatebin_db::getInstance(array(
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
        privatebin_db::getInstance(array(
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
        privatebin_db::getInstance($options);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 6
     */
    public function testMissingUsr()
    {
        $options = $this->_options;
        unset($options['usr']);
        privatebin_db::getInstance($options);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 6
     */
    public function testMissingPwd()
    {
        $options = $this->_options;
        unset($options['pwd']);
        privatebin_db::getInstance($options);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 6
     */
    public function testMissingOpt()
    {
        $options = $this->_options;
        unset($options['opt']);
        privatebin_db::getInstance($options);
    }

    public function testTableUpgrade()
    {
        mkdir(PATH . 'data');
        $path = PATH . 'data/db-test.sq3';
        @unlink($path);
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
        privatebin_db::getInstance($this->_options);
        @unlink($path);
    }
}
