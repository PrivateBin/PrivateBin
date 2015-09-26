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
        foreach (array('data', 'opendiscussion', 'formatter') as $key) {
            $this->assertEquals($pasteData[$key], $paste->$key);
        }

        // storing comments
        $commentData = helper::getComment();
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
     * @expectedExceptionCode 60
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
     * @expectedExceptionCode 60
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
        $this->assertEquals(true, $paste->meta->burnafterreading, 'burn after reading takes precedence');
        $this->assertEquals(false, $paste->meta->opendiscussion, 'opendiscussion is overiden');
        $this->assertEquals($this->_conf->getKey('defaultformatter'), $paste->meta->formatter, 'default formatter is set');

        $_SERVER['REMOTE_ADDR'] = '::1';
        $vz = new vizhash16x16();
        $pngdata = 'data:image/png;base64,' . base64_encode($vz->generate($_SERVER['REMOTE_ADDR']));
        $comment = $this->_model->getPaste(helper::getPasteId())->getComment(helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $comment = $paste->getComment(helper::getPasteId(), helper::getCommentId());
        $this->assertEquals($pngdata, $comment->meta->vizhash, 'nickname triggers vizhash to be set');
    }
}