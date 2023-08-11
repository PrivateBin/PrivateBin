<?php

use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use PrivateBin\Data\GoogleCloudStorage;

class GoogleCloudStorageTest extends TestCase
{
    private static $_client;
    private static $_bucket;

    public static function setUpBeforeClass(): void
    {
        $httpClient = new Client(array('debug'=>false));
        $handler    = HttpHandlerFactory::build($httpClient);

        $name     = 'pb-';
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < 29; ++$i) {
            $name .= $alphabet[rand(0, strlen($alphabet) - 1)];
        }
        self::$_client = new StorageClientStub(array());
        self::$_bucket = self::$_client->createBucket($name);
    }

    public function setUp(): void
    {
        ini_set('error_log', stream_get_meta_data(tmpfile())['uri']);
        $this->_model = new GoogleCloudStorage(array(
            'bucket' => self::$_bucket->name(),
            'prefix' => 'pastes',
        ));
    }

    public function tearDown(): void
    {
        foreach (self::$_bucket->objects() as $object) {
            $object->delete();
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$_bucket->delete();
    }

    public function testFileBasedDataStoreWorks()
    {
        $this->_model->delete(Helper::getPasteId());

        // storing pastes
        $paste = Helper::getPaste(2, array('expire_date' => 1344803344));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), $paste), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store the same paste twice');
        $this->assertEquals($paste, $this->_model->read(Helper::getPasteId()));

        // storing comments
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does not yet exist');
        $this->assertTrue($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), Helper::getComment()), 'store comment');
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment exists after storing it');
        $this->assertFalse($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), Helper::getComment()), 'unable to store the same comment twice');
        $comment             = Helper::getComment();
        $comment['id']       = Helper::getCommentId();
        $comment['parentid'] = Helper::getPasteId();
        $this->assertEquals(
            array($comment['meta']['created'] => $comment),
            $this->_model->readComments(Helper::getPasteId())
        );

        // deleting pastes
        $this->_model->delete(Helper::getPasteId());
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment was deleted with paste');
        $this->assertFalse($this->_model->read(Helper::getPasteId()), 'paste can no longer be found');
    }

    /**
     * pastes a-g are expired and should get deleted, x never expires and y-z expire in an hour
     */
    public function testPurge()
    {
        $expired = Helper::getPaste(2, array('expire_date' => 1344803344));
        $paste   = Helper::getPaste(2, array('expire_date' => time() + 3600));
        $keys    = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'x', 'y', 'z');
        $ids     = array();
        foreach ($keys as $key) {
            $ids[$key] = hash('fnv164', $key);
            $this->assertFalse($this->_model->exists($ids[$key]), "paste $key does not yet exist");
            if (in_array($key, array('x', 'y', 'z'))) {
                $this->assertTrue($this->_model->create($ids[$key], $paste), "store $key paste");
            } elseif ($key === 'x') {
                $this->assertTrue($this->_model->create($ids[$key], Helper::getPaste()), "store $key paste");
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
        $this->_model->delete(Helper::getPasteId());
        $paste = Helper::getPaste(2, array('expire' => "Invalid UTF-8 sequence: \xB1\x31"));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertFalse($this->_model->create(Helper::getPasteId(), $paste), 'unable to store broken paste');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does still not exist');
    }

    public function testCommentErrorDetection()
    {
        $this->_model->delete(Helper::getPasteId());
        $comment = Helper::getComment(1, array('nickname' => "Invalid UTF-8 sequence: \xB1\x31"));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not yet exist');
        $this->assertTrue($this->_model->create(Helper::getPasteId(), Helper::getPaste()), 'store new paste');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after storing it');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does not yet exist');
        $this->assertFalse($this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), $comment), 'unable to store broken comment');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment does still not exist');
    }

    /**
     * @throws Exception
     */
    public function testKeyValueStore()
    {
        $salt = bin2hex(random_bytes(256));
        $this->_model->setValue($salt, 'salt', '');
        $storedSalt = $this->_model->getValue('salt', '');
        $this->assertEquals($salt, $storedSalt);
        $this->_model->purgeValues('salt', time() + 60);
        $this->assertEquals('', $this->_model->getValue('salt', 'master'));

        $client = hash_hmac('sha512', '127.0.0.1', $salt);
        $expire = time();
        $this->_model->setValue(strval($expire), 'traffic_limiter', $client);
        $storedExpired = $this->_model->getValue('traffic_limiter', $client);
        $this->assertEquals(strval($expire), $storedExpired);

        $this->_model->purgeValues('traffic_limiter', time() - 60);
        $this->assertEquals($storedExpired, $this->_model->getValue('traffic_limiter', $client));
        $this->_model->purgeValues('traffic_limiter', time() + 60);
        $this->assertEquals('', $this->_model->getValue('traffic_limiter', $client));

        $purgeAt = $expire + (15 * 60);
        $this->_model->setValue(strval($purgeAt), 'purge_limiter', '');
        $storedPurgedAt = $this->_model->getValue('purge_limiter', '');
        $this->assertEquals(strval($purgeAt), $storedPurgedAt);
        $this->_model->purgeValues('purge_limiter', $purgeAt + 60);
        $this->assertEquals('', $this->_model->getValue('purge_limiter', ''));
        $this->assertEquals('', $this->_model->getValue('purge_limiter', 'at'));
    }

    /**
     * @throws Exception
     */
    public function testKeyValuePurgeTrafficLimiter()
    {
        $salt   = bin2hex(random_bytes(256));
        $client = hash_hmac('sha512', '127.0.0.1', $salt);
        $expire = time();
        $this->_model->setValue(strval($expire), 'traffic_limiter', $client);
        $storedExpired = $this->_model->getValue('traffic_limiter', $client);
        $this->assertEquals(strval($expire), $storedExpired);

        $this->_model->purgeValues('traffic_limiter', time() - 60);
        $this->assertEquals($storedExpired, $this->_model->getValue('traffic_limiter', $client));

        $this->_model->purgeValues('traffic_limiter', time() + 60);
        $this->assertEquals('', $this->_model->getValue('traffic_limiter', $client));
    }
}
