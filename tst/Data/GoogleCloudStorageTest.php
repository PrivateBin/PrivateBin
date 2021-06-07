<?php

use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Cloud\Core\Exception\BadRequestException;
use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\Connection\ConnectionInterface;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use GuzzleHttp\Client;
use PrivateBin\Data\GoogleCloudStorage;

class GoogleCloudStorageTest extends PHPUnit_Framework_TestCase
{
    private static $_client;
    private static $_bucket;

    public static function setUpBeforeClass()
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

    public function setUp()
    {
        ini_set('error_log', stream_get_meta_data(tmpfile())['uri']);
        $this->_model = GoogleCloudStorage::getInstance(array(
            'bucket' => self::$_bucket->name(),
            'prefix' => 'pastes',
            'client' => self::$_client, ));
    }

    public function tearDown()
    {
        foreach (self::$_bucket->objects() as $object) {
            $object->delete();
        }
        error_reporting(E_ALL);
    }

    public static function tearDownAfterClass()
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
        $this->_model->setValue($salt, 'salt', 'master');
        $storedSalt = $this->_model->getValue('salt', 'master');
        $this->assertEquals($salt, $storedSalt);

        $client = hash_hmac('sha512', '127.0.0.1', $salt);
        $expire = time();
        $this->_model->setValue($expire, 'traffic_limiter', $client);
        $storedExpired = $this->_model->getValue('traffic_limiter', $client);
        $this->assertEquals($expire, $storedExpired);

        $purgeAt = $expire + (15 * 60);
        $this->_model->setValue($purgeAt, 'purge_limiter', 'at');
        $storedPurgedAt = $this->_model->getValue('purge_limiter', 'at');
        $this->assertEquals($purgeAt, $storedPurgedAt);
    }
}

/**
 * Class StorageClientStub provides a limited stub for performing the unit test
 */
class StorageClientStub extends StorageClient
{
    private $_config     = null;
    private $_connection = null;
    private $_buckets    = array();

    public function __construct(array $config = array())
    {
        $this->_config     = $config;
        $this->_connection =  new ConnectionInterfaceStub();
    }

    public function bucket($name, $userProject = false)
    {
        if (!key_exists($name, $this->_buckets)) {
            $b                     = new BucketStub($this->_connection, $name, array(), $this);
            $this->_buckets[$name] = $b;
        }
        return $this->_buckets[$name];
    }

    /**
     * @throws \Google\Cloud\Core\Exception\NotFoundException
     */
    public function deleteBucket($name)
    {
        if (key_exists($name, $this->_buckets)) {
            unset($this->_buckets[$name]);
        } else {
            throw new NotFoundException();
        }
    }

    public function buckets(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function registerStreamWrapper($protocol = null)
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function unregisterStreamWrapper($protocol = null)
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function signedUrlUploader($uri, $data, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function timestamp(\DateTimeInterface $timestamp, $nanoSeconds = null)
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getServiceAccount(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function hmacKeys(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function hmacKey($accessId, $projectId = null, array $metadata = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function createHmacKey($serviceAccountEmail, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function createBucket($name, array $options = array())
    {
        if (key_exists($name, $this->_buckets)) {
            throw new BadRequestException('already exists');
        }
        $b                     = new BucketStub($this->_connection, $name, array(), $this);
        $this->_buckets[$name] = $b;
        return $b;
    }
}

/**
 * Class BucketStub stubs a GCS bucket.
 */
class BucketStub extends Bucket
{
    public $_objects;
    private $_name;
    private $_info;
    private $_connection;
    private $_client;

    public function __construct(ConnectionInterface $connection, $name, array $info = array(), $client = null)
    {
        $this->_name       = $name;
        $this->_info       = $info;
        $this->_connection = $connection;
        $this->_objects    = array();
        $this->_client     = $client;
    }

    public function acl()
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function defaultAcl()
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function exists()
    {
        return true;
    }

    public function upload($data, array $options = array())
    {
        if (!is_string($data) || !key_exists('name', $options)) {
            throw new BadMethodCallException('not supported by this stub');
        }

        $name                             = $options['name'];
        $generation                       = '1';
        $o                                = new StorageObjectStub($this->_connection, $name, $this, $generation, $options);
        $this->_objects[$options['name']] = $o;
        $o->setData($data);
    }

    public function uploadAsync($data, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getResumableUploader($data, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getStreamableUploader($data, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function object($name, array $options = array())
    {
        if (key_exists($name, $this->_objects)) {
            return $this->_objects[$name];
        } else {
            return new StorageObjectStub($this->_connection, $name, $this, null, $options);
        }
    }

    public function objects(array $options = array())
    {
        $prefix = key_exists('prefix', $options) ? $options['prefix'] : '';

        return new CallbackFilterIterator(
                new ArrayIterator($this->_objects),
                function ($current, $key, $iterator) use ($prefix) {
                    return substr($key, 0, strlen($prefix)) == $prefix;
                }
        );
    }

    public function createNotification($topic, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function notification($id)
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function notifications(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function delete(array $options = array())
    {
        $this->_client->deleteBucket($this->_name);
    }

    public function update(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function compose(array $sourceObjects, $name, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function info(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function reload(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function name()
    {
        return $this->_name;
    }

    public static function lifecycle(array $lifecycle = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function currentLifecycle(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function isWritable($file = null)
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function iam()
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function lockRetentionPolicy(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function signedUrl($expires, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function generateSignedPostPolicyV4($objectName, $expires, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }
}

/**
 * Class StorageObjectStub stubs a GCS storage object.
 */
class StorageObjectStub extends StorageObject
{
    private $_name;
    private $_data;
    private $_info;
    private $_bucket;
    private $_generation;
    private $_exists = false;
    private $_connection;

    public function __construct(ConnectionInterface $connection, $name, $bucket, $generation = null, array $info = array(), $encryptionKey = null, $encryptionKeySHA256 = null)
    {
        $this->_name       = $name;
        $this->_bucket     = $bucket;
        $this->_generation = $generation;
        $this->_info       = $info;
        $this->_connection = $connection;
    }

    public function acl()
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function exists(array $options = array())
    {
        return key_exists($this->_name, $this->_bucket->_objects);
    }

    /**
     * @throws NotFoundException
     */
    public function delete(array $options = array())
    {
        if (key_exists($this->_name, $this->_bucket->_objects)) {
            unset($this->_bucket->_objects[$this->_name]);
        } else {
            throw new NotFoundException('key ' . $this->_name . ' not found.');
        }
    }

    /**
     * @throws NotFoundException
     */
    public function update(array $metadata, array $options = array())
    {
        if (!$this->_exists) {
            throw new NotFoundException('key ' . $this->_name . ' not found.');
        }
        $this->_info = $metadata;
    }

    public function copy($destination, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function rewrite($destination, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function rename($name, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    /**
     * @throws NotFoundException
     */
    public function downloadAsString(array $options = array())
    {
        if (!$this->_exists) {
            throw new NotFoundException('key ' . $this->_name . ' not found.');
        }
        return $this->_data;
    }

    public function downloadToFile($path, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function downloadAsStream(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function downloadAsStreamAsync(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function signedUrl($expires, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function signedUploadUrl($expires, array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function beginSignedUploadSession(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function info(array $options = array())
    {
        return key_exists('metadata',$this->_info) ? $this->_info['metadata'] : array();
    }

    public function reload(array $options = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function name()
    {
        return $this->_name;
    }

    public function identity()
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function gcsUri()
    {
        return sprintf(
            'gs://%s/%s',
            $this->_bucket->name(),
            $this->_name
        );
    }

    public function setData($data)
    {
        $this->_data   = $data;
        $this->_exists = true;
    }
}

/**
 * Class ConnectionInterfaceStub required for the stubs.
 */
class ConnectionInterfaceStub implements ConnectionInterface
{
    public function deleteAcl(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getAcl(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function listAcl(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function insertAcl(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function patchAcl(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function deleteBucket(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getBucket(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function listBuckets(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function insertBucket(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getBucketIamPolicy(array $args)
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function setBucketIamPolicy(array $args)
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function testBucketIamPermissions(array $args)
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function patchBucket(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function deleteObject(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function copyObject(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function rewriteObject(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function composeObject(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getObject(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function listObjects(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function patchObject(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function downloadObject(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function insertObject(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getNotification(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function deleteNotification(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function insertNotification(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function listNotifications(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getServiceAccount(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function lockRetentionPolicy(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function createHmacKey(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function deleteHmacKey(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function getHmacKey(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function updateHmacKey(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }

    public function listHmacKeys(array $args = array())
    {
        throw new BadMethodCallException('not supported by this stub');
    }
}
