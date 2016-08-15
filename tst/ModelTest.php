<?php

use PrivateBin\Configuration;
use PrivateBin\Data\Database;
use PrivateBin\Model;
use PrivateBin\Model\Paste;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Persistence\TrafficLimiter;
use PrivateBin\Vizhash16x16;
use Identicon\Identicon;

class ModelTest extends PHPUnit_Framework_TestCase
{
    private $_conf;

    private $_model;

    protected $_path;

    public function setUp()
    {
        /* Setup Routine */
        Helper::confRestore();
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) mkdir($this->_path);
        ServerSalt::setPath($this->_path);
        $options = parse_ini_file(CONF, true);
        $options['purge']['limit'] = 0;
        $options['model'] = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite::memory:',
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $this->_conf = new Configuration;
        $this->_model = new Model($this->_conf);
        $_SERVER['REMOTE_ADDR'] = '::1';
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        Helper::confRestore();
        Helper::rmDir($this->_path);
    }

    public function testBasicWorkflow()
    {
        // storing pastes
        $pasteData = Helper::getPaste();
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(Helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste does not yet exist');

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();

        $paste = $this->_model->getPaste(Helper::getPasteId());
        $this->assertTrue($paste->exists(), 'paste exists after storing it');
        $paste = $paste->get();
        $this->assertEquals($pasteData['data'], $paste->data);
        foreach (array('opendiscussion', 'formatter') as $key) {
            $this->assertEquals($pasteData['meta'][$key], $paste->meta->$key);
        }

        // storing comments
        $commentData = Helper::getComment();
        $paste = $this->_model->getPaste(Helper::getPasteId());
        $comment = $paste->getComment(Helper::getPasteId(), Helper::getCommentId());
        $this->assertFalse($comment->exists(), 'comment does not yet exist');

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $comment = $paste->getComment(Helper::getPasteId(), Helper::getCommentId());
        $this->assertTrue($comment->exists(), 'comment exists after storing it');
        $comment = $comment->get();
        $this->assertEquals($commentData['data'], $comment->data);
        $this->assertEquals($commentData['meta']['nickname'], $comment->meta->nickname);

        // deleting pastes
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(Helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste successfully deleted');
        $this->assertEquals(array(), $paste->getComments(), 'comment was deleted with paste');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 75
     */
    public function testPasteDuplicate()
    {
        $pasteData = Helper::getPaste();

        $this->_model->getPaste(Helper::getPasteId())->delete();
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
        $pasteData = Helper::getPaste();
        $commentData = Helper::getComment();
        $this->_model->getPaste(Helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();
    }

    public function testImplicitDefaults()
    {
        $pasteData = Helper::getPaste();
        $commentData = Helper::getComment();
        $this->_model->getPaste(Helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setBurnafterreading();
        $paste->setOpendiscussion();
        // not setting a formatter, should use default one
        $paste->store();

        $paste = $this->_model->getPaste(Helper::getPasteId())->get(); // ID was set based on data
        $this->assertEquals(true, property_exists($paste->meta, 'burnafterreading') && $paste->meta->burnafterreading, 'burn after reading takes precendence');
        $this->assertEquals(false, property_exists($paste->meta, 'opendiscussion') && $paste->meta->opendiscussion, 'opendiscussion is disabled');
        $this->assertEquals($this->_conf->getKey('defaultformatter'), $paste->meta->formatter, 'default formatter is set');

        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setBurnafterreading('0');
        $paste->setOpendiscussion();
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $identicon = new Identicon();
        $pngdata = $identicon->getImageDataUri(TrafficLimiter::getHash(), 16);
        $comment = $paste->getComment(Helper::getPasteId(), Helper::getCommentId())->get();
        $this->assertEquals($pngdata, $comment->meta->vizhash, 'nickname triggers vizhash to be set');
    }

    public function testPasteIdValidation()
    {
        $this->assertTrue(Paste::isValidId('a242ab7bdfb2581a'), 'valid paste id');
        $this->assertFalse(Paste::isValidId('foo'), 'invalid hex values');
        $this->assertFalse(Paste::isValidId('../bar/baz'), 'path attack');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 62
     */
    public function testInvalidComment()
    {
        $paste = $this->_model->getPaste();
        $paste->getComment(Helper::getPasteId());
    }

    public function testExpiration()
    {
        $pasteData = Helper::getPaste();
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(Helper::getPasteId());
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
        $pasteData = Helper::getPaste();
        $this->_model->getPaste(Helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->store();
        $paste->getComment(Helper::getPasteId())->delete();
    }

    public function testPurge()
    {
        $conf = new Configuration;
        $store = Database::getInstance($conf->getSection('model_options'));
        $store->delete(Helper::getPasteId());
        $expired = Helper::getPaste(array('expire_date' => 1344803344));
        $paste = Helper::getPaste(array('expire_date' => time() + 3600));
        $keys = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'x', 'y', 'z');
        $ids = array();
        foreach ($keys as $key) {
            $ids[$key] = substr(md5($key), 0, 16);
            $store->delete($ids[$key]);
            $this->assertFalse($store->exists($ids[$key]), "paste $key does not yet exist");
            if (in_array($key, array('x', 'y', 'z'))) {
                $this->assertTrue($store->create($ids[$key], $paste), "store $key paste");
            } else {
                $this->assertTrue($store->create($ids[$key], $expired), "store $key paste");
            }
            $this->assertTrue($store->exists($ids[$key]), "paste $key exists after storing it");
        }
        $this->_model->purge(10);
        foreach ($ids as $key => $id) {
            if (in_array($key, array('x', 'y', 'z'))) {
                $this->assertTrue($this->_model->getPaste($id)->exists(), "paste $key exists after purge");
                $this->_model->getPaste($id)->delete();
            } else {
                $this->assertFalse($this->_model->getPaste($id)->exists(), "paste $key was purged");
            }
        }
    }

    public function testCommentWithDisabledVizhash()
    {
        $options = parse_ini_file(CONF, true);
        $options['main']['icon'] = 'none';
        $options['model'] = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite::memory:',
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $model = new Model(new Configuration);

        $pasteData = Helper::getPaste();
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $model->getPaste(Helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste does not yet exist');

        $paste = $model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();

        $paste = $model->getPaste(Helper::getPasteId());
        $this->assertTrue($paste->exists(), 'paste exists after storing it');
        $paste = $paste->get();
        $this->assertEquals($pasteData['data'], $paste->data);
        foreach (array('opendiscussion', 'formatter') as $key) {
            $this->assertEquals($pasteData['meta'][$key], $paste->meta->$key);
        }

        // storing comments
        $commentData = Helper::getComment();
        $paste = $model->getPaste(Helper::getPasteId());
        $comment = $paste->getComment(Helper::getPasteId(), Helper::getCommentId());
        $this->assertFalse($comment->exists(), 'comment does not yet exist');

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $comment = $paste->getComment(Helper::getPasteId(), Helper::getCommentId());
        $this->assertTrue($comment->exists(), 'comment exists after storing it');
        $comment = $comment->get();
        $this->assertEquals($commentData['data'], $comment->data);
        $this->assertEquals($commentData['meta']['nickname'], $comment->meta->nickname);
        $this->assertFalse(property_exists($comment->meta, 'vizhash'), 'vizhash was not generated');
    }

    public function testCommentIdenticon()
    {
        $options = parse_ini_file(CONF, true);
        $options['main']['icon'] = 'identicon';
        $options['model'] = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite::memory:',
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $model = new Model(new Configuration);

        $pasteData = Helper::getPaste();
        $commentData = Helper::getComment();
        $model->getPaste(Helper::getPasteId())->delete();

        $paste = $model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $identicon = new Identicon();
        $pngdata = $identicon->getImageDataUri(TrafficLimiter::getHash(), 16);
        $comment = $paste->getComment(Helper::getPasteId(), Helper::getCommentId())->get();
        $this->assertEquals($pngdata, $comment->meta->vizhash, 'nickname triggers vizhash to be set');
    }

    public function testCommentVizhash()
    {
        $options = parse_ini_file(CONF, true);
        $options['main']['icon'] = 'vizhash';
        $options['model'] = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite::memory:',
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $model = new Model(new Configuration);

        $pasteData = Helper::getPaste();
        $commentData = Helper::getComment();
        $model->getPaste(Helper::getPasteId())->delete();

        $paste = $model->getPaste();
        $paste->setData($pasteData['data']);
        $paste->setOpendiscussion();
        $paste->setFormatter($pasteData['meta']['formatter']);
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData['data']);
        $comment->setNickname($commentData['meta']['nickname']);
        $comment->store();

        $vz = new Vizhash16x16();
        $pngdata = 'data:image/png;base64,' . base64_encode($vz->generate(TrafficLimiter::getHash()));
        $comment = $paste->getComment(Helper::getPasteId(), Helper::getCommentId())->get();
        $this->assertEquals($pngdata, $comment->meta->vizhash, 'nickname triggers vizhash to be set');
    }
}
