<?php
class zerobin_dbTest extends PHPUnit_Framework_TestCase
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
        $this->_model = zerobin_db::getInstance($this->_options);
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

    /**
     * @expectedException PDOException
     */
    public function testGetIbmInstance()
    {
        zerobin_db::getInstance(array(
            'dsn' => 'ibm:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetInformixInstance()
    {
        zerobin_db::getInstance(array(
            'dsn' => 'informix:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetMssqlInstance()
    {
        zerobin_db::getInstance(array(
            'dsn' => 'mssql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetMysqlInstance()
    {
        zerobin_db::getInstance(array(
            'dsn' => 'mysql:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetOciInstance()
    {
        zerobin_db::getInstance(array(
            'dsn' => 'oci:', 'usr' => null, 'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        ));
    }

    /**
     * @expectedException PDOException
     */
    public function testGetPgsqlInstance()
    {
        zerobin_db::getInstance(array(
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
        zerobin_db::getInstance(array(
            'dsn' => 'foo:', 'usr' => null, 'pwd' => null, 'opt' => null
        ));
    }

    public function testTableUpgrade()
    {
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
        zerobin_db::getInstance($this->_options);
        @unlink($path);
    }
}
