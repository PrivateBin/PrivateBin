<?php declare(strict_types=1);

use Google\Cloud\Core\Exception\BadRequestException;
use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\Connection\ConnectionInterface;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use PrivateBin\Persistence\ServerSalt;

error_reporting(E_ALL | E_STRICT);

// change this, if your php files and data is outside of your webservers document root
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', '..');
}
if (!defined('PATH')) {
    define('PATH', '..' . DIRECTORY_SEPARATOR);
}
if (!defined('CONF')) {
    define('CONF', PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php');
}
if (!defined('CONF_SAMPLE')) {
    define('CONF_SAMPLE', PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.sample.php');
}

require PATH . 'vendor/autoload.php';
Helper::updateSubresourceIntegrity();

/**
 * Class Helper provides unit tests pastes and comments of various formats
 */
class Helper
{
    /**
     * example ID of a paste
     *
     * @var string
     */
    private static $pasteid = '5b65a01b43987bc2';

    /**
     * example paste version 1
     *
     * @var array
     */
    private static $pasteV1 = array(
        'data'           => '{"iv":"EN39/wd5Nk8HAiSG2K5AsQ","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"QKN1DBXe5PI","ct":"8hA83xDdXjD7K2qfmw5NdA"}',
        'attachment'     => '{"iv":"Pd4pOKWkmDTT9uPwVwd5Ag","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"6nOCU3peNDclDDpFtJEBKA"}',
        'attachmentname' => '{"iv":"76MkAtOGC4oFogX/aSMxRA","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"b6Ae/U1xJdsX/+lATud4sQ"}',
        'meta'           => array(
            'formatter'      => 'plaintext',
            'postdate'       => 1344803344,
            'opendiscussion' => true,
        ),
    );

    /**
     * example paste version 2
     *
     * @var array
     */
    private static $pasteV2 = array(
        'adata' => array(
            array(
                'gMSNoLOk4z0RnmsYwXZ8mw==',
                'TZO+JWuIuxs=',
                100000,
                256,
                128,
                'aes',
                'gcm',
                'zlib',
            ),
            'plaintext',
            1,
            0,
        ),
        'meta' => array(
            'expire'  => '5min',
        ),
        'v'  => 2,
        'ct' => 'ME5JF/YBEijp2uYMzLZozbKtWc5wfy6R59NBb7SmRig=',
    );

    /**
     * example ID of a comment
     *
     * @var string
     */
    private static $commentid = '5a52eebf11c4c94b';

    /**
     * example comment
     *
     * @var array
     */
    private static $commentV1 = array(
        'data' => '{"iv":"Pd4pOKWkmDTT9uPwVwd5Ag","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"6nOCU3peNDclDDpFtJEBKA"}',
        'meta' => array(
            'nickname' => '{"iv":"76MkAtOGC4oFogX/aSMxRA","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"b6Ae/U1xJdsX/+lATud4sQ"}',
            'vizhash'  => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAABGUlEQVQokWOsl5/94983CNKQMjnxaOePf98MeKwPfNjkLZ3AgARab6b9+PeNEVnDj3/ff/z7ZiHnzsDA8Pv7H2TVPJw8EAYLAwb48OaVgIgYKycLsrYv378wMDB8//qdCVMDRA9EKSsnCwRBxNsepaLboMFlyMDAICAi9uHNK24GITQ/MDAwoNhgIGMLtwGrzegaLjw5jMz9+vUdnN17uwDCQDhJgk0O07yvX9+teDX1x79v6DYIsIjgcgMaYGFgYOBg4kJx2JejkAiBxAw+PzAwMNz4dp6wDXDw4MdNNOl0rWYsNkD89OLXI/xmo9sgzatJjAYmBgYGDiauD3/ePP18nVgb4MF89+M5ZX6js293wUMpnr8KTQMAxsCJnJ30apMAAAAASUVORK5CYII=',
            'postdate' => 1344803528,
        ),
    );

    /**
     * JS files and their SRI hashes
     *
     * @var array
     */
    private static $hashes = array();

    /**
     * get example paste ID
     *
     * @return string
     */
    public static function getPasteId(): string
    {
        return self::$pasteid;
    }

    /**
     * get example paste, as stored on server
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getPaste($version = 2, array $meta = array()): array
    {
        $example = self::getPasteWithAttachment($version, $meta);
        // v1 has the attachment stored in a separate property
        if ($version === 1) {
            unset($example['attachment'], $example['attachmentname']);
        }
        return $example;
    }

    /**
     * get example paste with attachment, as stored on server
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getPasteWithAttachment($version = 2, array $meta = array()): array
    {
        $example                 = $version === 1 ? self::$pasteV1 : self::$pasteV2;
        $example['meta']['salt'] = ServerSalt::generate();
        $example['meta']         = array_merge($example['meta'], $meta);
        return $example;
    }

    /**
     * get example paste, as decoded from POST by the request object
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getPastePost($version = 2, array $meta = array()): array
    {
        $example         = self::getPaste($version, $meta);
        if ($version == 2) {
            $example['meta'] = array('expire' => $example['meta']['expire']);
        } else {
            unset($example['meta']['postdate']);
        }
        return $example;
    }

    /**
     * get example paste, as received via POST by the user
     *
     * @param  int $version
     * @param  array $meta
     * @return string
     */
    public static function getPasteJson($version = 2, array $meta = array()): string
    {
        return json_encode(self::getPastePost($version, $meta));
    }

    /**
     * get example paste ID
     *
     * @return string
     */
    public static function getCommentId(): string
    {
        return self::$commentid;
    }

    /**
     * get example comment, as stored on server
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getComment($version = 2, array $meta = array()): array
    {
        $example         = $version === 1 ? self::$commentV1 : self::$pasteV2;
        if ($version === 2) {
            $example['adata']           = $example['adata'][0];
            $example['pasteid']         = $example['parentid']         = self::getPasteId();
            $example['meta']['created'] = self::$commentV1['meta']['postdate'];
            $example['meta']['icon']    = self::$commentV1['meta']['vizhash'];
            unset($example['meta']['expire']);
        }
        $example['meta'] = array_merge($example['meta'], $meta);
        return $example;
    }

    /**
     * get example comment, as decoded from POST by the request object
     *
     * @param  int $version
     * @return array
     */
    public static function getCommentPost(): array
    {
        $example = self::getComment();
        unset($example['meta']);
        return $example;
    }

    /**
     * get example comment, as received via POST by user
     *
     * @param  int $version
     * @return string
     */
    public static function getCommentJson(): string
    {
        return json_encode(self::getCommentPost());
    }

    /**
     * Returns 16 random hexadecimal characters.
     *
     * @return string
     */
    public static function getRandomId(): string
    {
        // 8 binary bytes are 16 characters long in hex
        return bin2hex(random_bytes(8));
    }

    /**
     * delete directory and all its contents recursively
     *
     * @param string $path
     * @throws Exception
     * @return void
     */
    public static function rmDir($path): void
    {
        if (is_dir($path)) {
            $path .= DIRECTORY_SEPARATOR;
            $dir = dir($path);
            while (false !== ($file = $dir->read())) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . $file)) {
                        self::rmDir($path . $file);
                    } elseif (is_file($path . $file)) {
                        if (!unlink($path . $file)) {
                            throw new Exception('Error deleting file "' . $path . $file . '".');
                        }
                    }
                }
            }
            $dir->close();
            if (!rmdir($path)) {
                throw new Exception('Error deleting directory "' . $path . '".');
            }
        }
    }

    /**
     * create a backup of the config file
     *
     * @return void
     */
    public static function confBackup(): void
    {
        if (!is_file(CONF . '.bak') && is_file(CONF)) {
            rename(CONF, CONF . '.bak');
        }
        if (!is_file(CONF_SAMPLE . '.bak') && is_file(CONF_SAMPLE)) {
            copy(CONF_SAMPLE, CONF_SAMPLE . '.bak');
        }
    }

    /**
     * restor backup of the config file
     *
     * @return void
     */
    public static function confRestore(): void
    {
        if (is_file(CONF . '.bak')) {
            rename(CONF . '.bak', CONF);
        }
        if (is_file(CONF_SAMPLE . '.bak')) {
            rename(CONF_SAMPLE . '.bak', CONF_SAMPLE);
        }
    }

    /**
     * create ini file
     *
     * @param string $pathToFile
     * @param array $values
     */
    public static function createIniFile($pathToFile, array $values): void
    {
        if (count($values)) {
            @unlink($pathToFile);
            $ini = fopen($pathToFile, 'a');
            foreach ($values as $section => $options) {
                fwrite($ini, "[$section]" . PHP_EOL);
                foreach ($options as $option => $setting) {
                    if (is_null($setting)) {
                        continue;
                    } elseif (is_string($setting)) {
                        $setting = '"' . $setting . '"';
                    } elseif (is_array($setting)) {
                        foreach ($setting as $key => $value) {
                            if (is_null($value)) {
                                $value = 'null';
                            } elseif (is_string($value)) {
                                $value = '"' . $value . '"';
                            } else {
                                $value = var_export($value, true);
                            }
                            fwrite($ini, $option . "[$key] = $value" . PHP_EOL);
                        }
                        continue;
                    } else {
                        $setting = var_export($setting, true);
                    }
                    fwrite($ini, "$option = $setting" . PHP_EOL);
                }
                fwrite($ini, PHP_EOL);
            }
            fclose($ini);
        }
    }

    /**
     * a var_export that returns arrays without line breaks
     * by linus@flowingcreativity.net via php.net
     *
     * @param mixed $var
     * @param bool $return
     * @return void|string
     */
    public static function varExportMin($var, $return = false): string
    {
        if (is_array($var)) {
            $toImplode = array();
            foreach ($var as $key => $value) {
                $toImplode[] = var_export($key, true) . ' => ' . self::varExportMin($value, true);
            }
            $code = 'array(' . implode(', ', $toImplode) . ')';
            if ($return) {
                return $code;
            } else {
                echo $code;
            }
        } else {
            return var_export($var, $return);
        }
    }

    /**
     * update all templates with the latest SRI hashes for all JS files
     *
     * @return void
     */
    public static function updateSubresourceIntegrity(): void
    {
        foreach (new GlobIterator(PATH . 'js' . DIRECTORY_SEPARATOR . '*.js') as $file) {
            if ($file->getBasename() == 'common.js') {
                continue; // ignore JS unit test bootstrap
            }
            self::$hashes[$file->getBasename()] = base64_encode(
                hash('sha512', file_get_contents($file->getPathname()), true)
            );
        }

        $counter = 0;
        $file    = PATH . 'lib' . DIRECTORY_SEPARATOR . 'Configuration.php';
        $content = preg_replace_callback(
            '#\'js/([a-z0-9.-]+.js)(\' +)=\> \'[^\']*\',#',
            function ($matches) use (&$counter) {
                if (array_key_exists($matches[1], Helper::$hashes)) {
                    ++$counter;
                    return '\'js/' . $matches[1] . $matches[2] .
                        '=> \'sha512-' . Helper::$hashes[$matches[1]] . '\',';
                } else {
                    throw new Exception('SRI hash for file js/' . $matches[1] . ' not found, please add the missing file or remove it from lib/Configuration.php.');
                }
            },
            file_get_contents($file)
        );
        file_put_contents($file, $content);
        if ($counter != count(self::$hashes)) {
            throw new Exception('Mismatch between ' . count(self::$hashes) . ' found js files and ' . $counter . ' SRI hashes in lib/Configuration.php, please update lib/Configuration.php to match the list of js files.');
        }
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

    public function exists(array $options = array())
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
        $this->_name                            = $name;
        $this->_bucket                          = $bucket;
        $this->_generation                      = $generation;
        $this->_info                            = $info;
        $this->_connection                      = $connection;
        $timeCreated                            = new DateTime();
        $this->_info['metadata']['timeCreated'] = $timeCreated->format('Y-m-d\TH:i:s.u\Z');
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

    public function restoreObject(array $args = array())
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

/**
 * Class StorageClientStub provides a limited stub for performing the unit test
 */
class StorageClientStub extends StorageClient
{
    private $_config         = null;
    private $_connection     = null;
    private static $_buckets = array();

    public function __construct(array $config = array())
    {
        $this->_config     = $config;
        $this->_connection =  new ConnectionInterfaceStub();
    }

    public function bucket($name, $userProject = false)
    {
        if (!key_exists($name, self::$_buckets)) {
            $b                     = new BucketStub($this->_connection, $name, array(), $this);
            self::$_buckets[$name] = $b;
        }
        return self::$_buckets[$name];
    }

    /**
     * @throws \Google\Cloud\Core\Exception\NotFoundException
     */
    public function deleteBucket($name)
    {
        if (key_exists($name, self::$_buckets)) {
            unset(self::$_buckets[$name]);
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
        if (key_exists($name, self::$_buckets)) {
            throw new BadRequestException('already exists');
        }
        $b                     = new BucketStub($this->_connection, $name, array(), $this);
        self::$_buckets[$name] = $b;
        return $b;
    }
}
