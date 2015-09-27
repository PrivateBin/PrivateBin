<?php
class modelTest extends PHPUnit_Framework_TestCase
{
    private $_conf;

    private $_model;

    public function setUp()
    {
        /* Setup Routine */
        $options = parse_ini_file(CONF, true);
        $options['model'] = array(
            'class' => 'zerobin_db',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite::memory:',
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $this->_conf = new configuration;
        $this->_model = new model($this->_conf);
        $_SERVER['REMOTE_ADDR'] = '::1';
    }

    public function tearDown()
    {
        /* Tear Down Routine */
    }

    public function testBasicWorkflow()
    {
        // storing pastes
        $pasteData = helper::getPaste();
        $this->_model->getPaste(helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste does not yet exist');

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();

        $paste = $this->_model->getPaste(helper::getPasteId());
        $this->assertTrue($paste->exists(), 'paste exists after storing it');
        $paste = $paste->get();
        $this->assertEquals($pasteData['data'], $paste->data);
        foreach (array('opendiscussion', 'formatter') as $key) {
            $this->assertEquals($pasteData['meta'][$key], $paste->meta->$key);
        }

        // storing comments
        $commentData = helper::getComment();
        $paste = $this->_model->getPaste(helper::getPasteId());
        $comment = $paste->getComment(helper::getPasteId(), helper::getCommentId());
        $this->assertFalse($comment->exists(), 'comment does not yet exist');

        $comment = $paste->getComment(helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $comment = $paste->getComment(helper::getPasteId(), helper::getCommentId());
        $this->assertTrue($comment->exists(), 'comment exists after storing it');
        $comment = $comment->get();
        $this->assertEquals($commentData['data'], $comment->data);
        $this->assertEquals($commentData['meta']['nickname'], $comment->meta->nickname);

        // deleting pastes
        $this->_model->getPaste(helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste successfully deleted');
        $this->assertEquals(array(), $paste->getComments(), 'comment was deleted with paste');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 75
     */
    public function testPasteDuplicate()
    {
        $pasteData = helper::getPaste();

        $this->_model->getPaste(helper::getPasteId())->delete();
        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 69
     */
    public function testCommentDuplicate()
    {
        $pasteData = helper::getPaste();
        $commentData = helper::getComment();
        $this->_model->getPaste(helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();

        $comment = $paste->getComment(helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $comment = $paste->getComment(helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();
    }

    public function testImplicitDefaults()
    {
        $pasteData = helper::getPaste();
        $commentData = helper::getComment();
        $this->_model->getPaste(helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setBurnafterreading();
        $paste->setOpendiscussion();
        // not setting a formatter, should use default one
        $paste->store();

        $paste = $this->_model->getPaste(helper::getPasteId())->get(); // ID was set based on data
        $this->assertEquals(true, property_exists($paste->meta, 'burnafterreading') && $paste->meta->burnafterreading, 'burn after reading takes precendence');
        $this->assertEquals(false, property_exists($paste->meta, 'opendiscussion') && $paste->meta->opendiscussion, 'opendiscussion is disabled');
        $this->assertEquals($this->_conf->getKey('defaultformatter'), $paste->meta->formatter, 'default formatter is set');

        $this->_model->getPaste(helper::getPasteId())->delete();
        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setBurnafterreading('0');
        $paste->setOpendiscussion();
        $paste->store();

        $vz = new vizhash16x16();
        $pngdata = 'data:image/png;base64,' . base64_encode($vz->generate($_SERVER['REMOTE_ADDR']));
        $comment = $paste->getComment(helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $comment = $paste->getComment(helper::getPasteId(), helper::getCommentId())->get();
        $this->assertEquals($pngdata, $comment->meta->vizhash, 'nickname triggers vizhash to be set');
    }

    public function testPasteIdValidation()
    {
        $this->assertTrue(model_paste::isValidId('a242ab7bdfb2581a'), 'valid paste id');
        $this->assertFalse(model_paste::isValidId('foo'), 'invalid hex values');
        $this->assertFalse(model_paste::isValidId('../bar/baz'), 'path attack');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 62
     */
    public function testInvalidComment()
    {
        $paste = $this->_model->getPaste();
        $comment = $paste->getComment(helper::getPasteId());
    }

    public function testExpiration()
    {
        $pasteData = helper::getPaste();
        $this->_model->getPaste(helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste does not yet exist');

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setExpiration('5min'); // = 300 seconds
        $paste->store();

        $paste = $paste->get();
        $this->assertEquals(300, $paste->meta->remaining_time, 'remaining time is set correctly');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 64
     */
    public function testCommentDeletion()
    {
        $pasteData = helper::getPaste();
        $this->_model->getPaste(helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->store();
        $paste->getComment(helper::getPasteId())->delete();
    }
}