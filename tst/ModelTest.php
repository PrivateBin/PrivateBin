<?php

use Identicon\Identicon;
use PHPUnit\Framework\TestCase;
use PrivateBin\Configuration;
use PrivateBin\Data\Database;
use PrivateBin\Model;
use PrivateBin\Model\Comment;
use PrivateBin\Model\Paste;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Persistence\TrafficLimiter;
use PrivateBin\Vizhash16x16;

class ModelTest extends TestCase
{
    private $_conf;

    private $_model;

    protected $_path;

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        $options                   = parse_ini_file(CONF_SAMPLE, true);
        $options['purge']['limit'] = 0;
        $options['model']          = array(
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
        ServerSalt::setStore(new Database($options['model_options']));
        $this->_conf            = new Configuration;
        $this->_model           = new Model($this->_conf);
        $_SERVER['REMOTE_ADDR'] = '::1';
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        unlink(CONF);
        Helper::confRestore();
        Helper::rmDir($this->_path);
    }

    public function testBasicWorkflow()
    {
        // storing pastes
        $pasteData = Helper::getPastePost();
        unset($pasteData['meta']['created'], $pasteData['meta']['salt']);
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(Helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste does not yet exist');

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData);
        $paste->store();

        $paste = $this->_model->getPaste(Helper::getPasteId());
        $this->assertTrue($paste->exists(), 'paste exists after storing it');
        $paste = $paste->get();
        unset(
            $pasteData['meta'],
            $paste['meta'],
            $paste['comments'],
            $paste['comment_count'],
            $paste['comment_offset'],
            $paste['@context']
        );
        $this->assertEquals($pasteData, $paste);

        // storing comments
        $commentData = Helper::getCommentPost();
        $paste       = $this->_model->getPaste(Helper::getPasteId());
        $comment     = $paste->getComment(Helper::getPasteId(), Helper::getCommentId());
        $this->assertFalse($comment->exists(), 'comment does not yet exist');

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData);
        $comment->store();

        $comments = $this->_model->getPaste(Helper::getPasteId())->get()['comments'];
        $this->assertTrue(count($comments) === 1, 'comment exists after storing it');
        $commentData['id']              = Helper::getPasteId();
        $commentData['meta']['created'] = current($comments)['meta']['created'];
        $commentData['meta']['icon']    = current($comments)['meta']['icon'];
        $this->assertEquals($commentData, current($comments));

        // deleting pastes
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(Helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste successfully deleted');
        $this->assertEquals(array(), $paste->getComments(), 'comment was deleted with paste');
    }

    public function testPasteV1()
    {
        $pasteData = Helper::getPaste(1);
        unset($pasteData['meta']['formatter']);

        $path = $this->_path . DIRECTORY_SEPARATOR . 'v1-test.sq3';
        if (is_file($path)) {
            unlink($path);
        }
        $options                   = parse_ini_file(CONF_SAMPLE, true);
        $options['purge']['limit'] = 0;
        $options['model']          = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite:' . $path,
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::createIniFile(CONF, $options);
        $model = new Model(new Configuration);
        $model->getPaste('0000000000000000')->exists(); // triggers database table creation
        $model->getPaste(Helper::getPasteId())->delete(); // deletes the cache

        $db = new PDO(
            $options['model_options']['dsn'],
            $options['model_options']['usr'],
            $options['model_options']['pwd'],
            $options['model_options']['opt']
        );
        $statement = $db->prepare('INSERT INTO paste VALUES(?,?,?,?,?,?,?,?,?)');
        $statement->execute(
            array(
                Helper::getPasteId(),
                $pasteData['data'],
                $pasteData['meta']['postdate'],
                0,
                0,
                0,
                json_encode($pasteData['meta']),
                null,
                null,
            )
        );
        $statement->closeCursor();

        $paste = $model->getPaste(Helper::getPasteId());
        $this->assertNotEmpty($paste->getDeleteToken(), 'excercise the condition to load the data from storage');
        $this->assertEquals('plaintext', $paste->get()['meta']['formatter'], 'paste got created with default formatter');
    }

    public function testCommentDefaults()
    {
        $class   = 'PrivateBin\\Data\\' . $this->_conf->getKey('class', 'model');
        $comment = new Comment(
            $this->_conf,
            new $class(
                $this->_conf->getSection('model_options')
            )
        );
        $comment->setPaste($this->_model->getPaste(Helper::getPasteId()));
        $this->assertEquals(Helper::getPasteId(), $comment->getParentId(), 'comment parent ID gets initialized to paste ID');
    }

    public function testPasteDuplicate()
    {
        $pasteData = Helper::getPastePost();

        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste();
        $paste->setData($pasteData);
        $paste->store();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(75);
        $paste->store();
    }

    public function testStoreFail()
    {
        $path = $this->_path . DIRECTORY_SEPARATOR . 'model-store-test.sq3';
        if (is_file($path)) {
            unlink($path);
        }
        $options                   = parse_ini_file(CONF_SAMPLE, true);
        $options['purge']['limit'] = 0;
        $options['model']          = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite:' . $path,
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::createIniFile(CONF, $options);
        $model = new Model(new Configuration);

        $pasteData   = Helper::getPastePost();
        $model->getPaste(Helper::getPasteId())->delete();
        $model->getPaste(Helper::getPasteId())->exists();

        $db = new PDO(
            $options['model_options']['dsn'],
            $options['model_options']['usr'],
            $options['model_options']['pwd'],
            $options['model_options']['opt']
        );
        $statement = $db->prepare('DROP TABLE paste');
        $statement->execute();
        $statement->closeCursor();

        $paste = $model->getPaste();
        $paste->setData($pasteData);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(76);
        $paste->store();
    }

    public function testCommentStoreFail()
    {
        $path = $this->_path . DIRECTORY_SEPARATOR . 'model-test.sq3';
        if (is_file($path)) {
            unlink($path);
        }
        $options                   = parse_ini_file(CONF_SAMPLE, true);
        $options['purge']['limit'] = 0;
        $options['model']          = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite:' . $path,
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::createIniFile(CONF, $options);
        $model = new Model(new Configuration);

        $pasteData   = Helper::getPastePost();
        $commentData = Helper::getCommentPost();
        $model->getPaste(Helper::getPasteId())->delete();

        $paste = $model->getPaste();
        $paste->setData($pasteData);
        $paste->store();
        $this->assertTrue($paste->exists(), 'paste exists before creating comment');

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData);

        $db = new PDO(
            $options['model_options']['dsn'],
            $options['model_options']['usr'],
            $options['model_options']['pwd'],
            $options['model_options']['opt']
        );
        $statement = $db->prepare('DROP TABLE comment');
        $statement->execute();
        $statement->closeCursor();

        $this->expectException(Exception::class);
        $this->expectExceptionCode(70);
        $comment->store();
    }

    public function testCommentDuplicate()
    {
        $pasteData   = Helper::getPastePost();
        $commentData = Helper::getCommentPost();
        $this->_model->getPaste(Helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData);
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData);
        $comment->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(69);
        $comment->store();
    }

    public function testImplicitDefaults()
    {
        $pasteData   = Helper::getPastePost();
        $commentData = Helper::getCommentPost();
        $this->_model->getPaste(Helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData);
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData);
        $comment->get();
        $comment->store();

        $identicon = new Identicon();
        $pngdata   = $identicon->getImageDataUri(TrafficLimiter::getHash(), 16);
        $comment   = current($this->_model->getPaste(Helper::getPasteId())->get()['comments']);
        $this->assertEquals($pngdata, $comment['meta']['icon'], 'icon gets set');
    }

    public function testPasteIdValidation()
    {
        $this->assertTrue(Paste::isValidId('a242ab7bdfb2581a'), 'valid paste id');
        $this->assertFalse(Paste::isValidId('foo'), 'invalid hex values');
        $this->assertFalse(Paste::isValidId('../bar/baz'), 'path attack');
    }

    public function testInvalidPaste()
    {
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(Helper::getPasteId());
        $this->expectException(Exception::class);
        $this->expectExceptionCode(64);
        $paste->get();
    }

    public function testInvalidPasteFormat()
    {
        $pasteData             = Helper::getPastePost();
        $pasteData['adata'][1] = 'format does not exist';
        $paste                 = $this->_model->getPaste();
        $this->expectException(Exception::class);
        $this->expectExceptionCode(75);
        $paste->setData($pasteData);
    }

    public function testInvalidPasteId()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(60);
        $this->_model->getPaste('');
    }

    public function testInvalidComment()
    {
        $paste = $this->_model->getPaste();
        $this->expectException(Exception::class);
        $this->expectExceptionCode(62);
        $paste->getComment(Helper::getPasteId());
    }

    public function testInvalidCommentDeletedPaste()
    {
        $pasteData = Helper::getPastePost();
        $paste     = $this->_model->getPaste(Helper::getPasteId());
        $paste->setData($pasteData);
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $paste->delete();
        $this->expectException(Exception::class);
        $this->expectExceptionCode(67);
        $comment->store();
    }

    public function testInvalidCommentData()
    {
        $pasteData             = Helper::getPastePost();
        $pasteData['adata'][2] = 0;
        $paste                 = $this->_model->getPaste(Helper::getPasteId());
        $paste->setData($pasteData);
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $this->expectException(Exception::class);
        $this->expectExceptionCode(68);
        $comment->store();
    }

    public function testInvalidCommentParent()
    {
        $paste     = $this->_model->getPaste(Helper::getPasteId());
        $this->expectException(Exception::class);
        $this->expectExceptionCode(65);
        $paste->getComment('');
    }

    public function testExpiration()
    {
        $pasteData = Helper::getPastePost();
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $this->_model->getPaste(Helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste does not yet exist');

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData);
        $paste->store();

        $paste = $paste->get();
        $this->assertEquals((float) 300, (float) $paste['meta']['time_to_live'], 'remaining time is set correctly', 1.0);
    }

    public function testCommentDeletion()
    {
        $pasteData = Helper::getPastePost();
        $this->_model->getPaste(Helper::getPasteId())->delete();

        $paste = $this->_model->getPaste();
        $paste->setData($pasteData);
        $paste->store();
        $this->expectException(Exception::class);
        $this->expectExceptionCode(64);
        $paste->getComment(Helper::getPasteId())->delete();
    }

    public function testPurge()
    {
        $conf  = new Configuration;
        $store = new Database($conf->getSection('model_options'));
        $store->delete(Helper::getPasteId());
        $expired = Helper::getPaste(2, array('expire_date' => 1344803344));
        $paste   = Helper::getPaste(2, array('expire_date' => time() + 3600));
        $keys    = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'x', 'y', 'z');
        $ids     = array();
        foreach ($keys as $key) {
            $ids[$key] = hash('fnv164', $key);
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
        $options                 = parse_ini_file(CONF, true);
        $options['main']['icon'] = 'none';
        $options['model']        = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite::memory:',
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::createIniFile(CONF, $options);
        $model = new Model(new Configuration);

        $pasteData = Helper::getPastePost();
        $this->_model->getPaste(Helper::getPasteId())->delete();
        $paste = $model->getPaste(Helper::getPasteId());
        $this->assertFalse($paste->exists(), 'paste does not yet exist');

        $paste = $model->getPaste();
        $paste->setData($pasteData);
        $paste->store();

        $paste = $model->getPaste(Helper::getPasteId());
        $this->assertTrue($paste->exists(), 'paste exists after storing it');

        // storing comments
        $commentData = Helper::getCommentPost();
        unset($commentData['meta']['icon']);
        $paste       = $model->getPaste(Helper::getPasteId());
        $comment     = $paste->getComment(Helper::getPasteId(), Helper::getPasteId());
        $this->assertFalse($comment->exists(), 'comment does not yet exist');

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData);
        $comment->store();

        $comment = $paste->getComment(Helper::getPasteId(), Helper::getPasteId());
        $this->assertTrue($comment->exists(), 'comment exists after storing it');

        $comment = current($this->_model->getPaste(Helper::getPasteId())->get()['comments']);
        $this->assertFalse(array_key_exists('icon', $comment['meta']), 'icon was not generated');
    }

    public function testCommentVizhash()
    {
        $options                 = parse_ini_file(CONF, true);
        $options['main']['icon'] = 'vizhash';
        $options['model']        = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite::memory:',
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        Helper::createIniFile(CONF, $options);
        $model = new Model(new Configuration);

        $pasteData   = Helper::getPastePost();
        $commentData = Helper::getCommentPost();
        $model->getPaste(Helper::getPasteId())->delete();

        $paste = $model->getPaste();
        $paste->setData($pasteData);
        $paste->store();

        $comment = $paste->getComment(Helper::getPasteId());
        $comment->setData($commentData);
        $comment->store();

        $vz        = new Vizhash16x16();
        $pngdata   = 'data:image/png;base64,' . base64_encode($vz->generate(TrafficLimiter::getHash()));
        $comment   = current($this->_model->getPaste(Helper::getPasteId())->get()['comments']);
        $this->assertEquals($pngdata, $comment['meta']['icon'], 'nickname triggers vizhash to be set');
    }
}
