<?php

namespace PrivateBin\Data;

use Exception;
use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use PrivateBin\Json;

class GoogleCloudStorage extends AbstractData
{
    /**
     * GCS client
     *
     * @access private
     * @static
     * @var    StorageClient
     */
    private static $_client = null;

    /**
     * GCS bucket
     *
     * @access private
     * @static
     * @var    Bucket
     */
    private static $_bucket = null;

    /**
     * object prefix
     *
     * @access private
     * @static
     * @var    string
     */
    private static $_prefix = 'pastes';

    /**
     * returns a Google Cloud Storage data backend.
     *
     * @access public
     * @static
     * @param array $options
     * @return GoogleCloudStorage
     */
    public static function getInstance(array $options)
    {
        // if needed initialize the singleton
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }

        $bucket = null;
        if (getenv('PRIVATEBIN_GCS_BUCKET')) {
            $bucket = getenv('PRIVATEBIN_GCS_BUCKET');
        }
        if (is_array($options) && array_key_exists('bucket', $options)) {
            $bucket = $options['bucket'];
        }
        if (is_array($options) && array_key_exists('prefix', $options)) {
            self::$_prefix = $options['prefix'];
        }

        if (empty(self::$_client)) {
            self::$_client = class_exists('StorageClientStub', false) ?
                new \StorageClientStub(array()) :
                new StorageClient(array('suppressKeyFileNotice' => true));
        }
        self::$_bucket = self::$_client->bucket($bucket);

        return self::$_instance;
    }

    /**
     * returns the google storage object key for $pasteid in self::$_bucket.
     *
     * @access private
     * @param $pasteid string to get the key for
     * @return string
     */
    private function _getKey($pasteid)
    {
        if (self::$_prefix != '') {
            return self::$_prefix . '/' . $pasteid;
        }
        return $pasteid;
    }

    /**
     * Uploads the payload in the self::$_bucket under the specified key.
     * The entire payload is stored as a JSON document. The metadata is replicated
     * as the GCS object's metadata except for the fields attachment, attachmentname
     * and salt.
     *
     * @param $key string to store the payload under
     * @param $payload array to store
     * @return bool true if successful, otherwise false.
     */
    private function _upload($key, $payload)
    {
        $metadata = array_key_exists('meta', $payload) ? $payload['meta'] : array();
        unset($metadata['attachment'], $metadata['attachmentname'], $metadata['salt']);
        foreach ($metadata as $k => $v) {
            $metadata[$k] = strval($v);
        }
        try {
            self::$_bucket->upload(Json::encode($payload), array(
                'name'          => $key,
                'chunkSize'     => 262144,
                'predefinedAcl' => 'private',
                'metadata'      => array(
                    'content-type' => 'application/json',
                    'metadata'     => $metadata,
                ),
            ));
        } catch (Exception $e) {
            error_log('failed to upload ' . $key . ' to ' . self::$_bucket->name() . ', ' .
                trim(preg_replace('/\s\s+/', ' ', $e->getMessage())));
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function create($pasteid, array $paste)
    {
        if ($this->exists($pasteid)) {
            return false;
        }

        return $this->_upload($this->_getKey($pasteid), $paste);
    }

    /**
     * @inheritDoc
     */
    public function read($pasteid)
    {
        try {
            $o    = self::$_bucket->object($this->_getKey($pasteid));
            $data = $o->downloadAsString();
            return Json::decode($data);
        } catch (NotFoundException $e) {
            return false;
        } catch (Exception $e) {
            error_log('failed to read ' . $pasteid . ' from ' . self::$_bucket->name() . ', ' .
                trim(preg_replace('/\s\s+/', ' ', $e->getMessage())));
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($pasteid)
    {
        $name = $this->_getKey($pasteid);

        try {
            foreach (self::$_bucket->objects(array('prefix' => $name . '/discussion/')) as $comment) {
                try {
                    self::$_bucket->object($comment->name())->delete();
                } catch (NotFoundException $e) {
                    // ignore if already deleted.
                }
            }
        } catch (NotFoundException $e) {
            // there are no discussions associated with the paste
        }

        try {
            self::$_bucket->object($name)->delete();
        } catch (NotFoundException $e) {
            // ignore if already deleted
        }
    }

    /**
     * @inheritDoc
     */
    public function exists($pasteid)
    {
        $o = self::$_bucket->object($this->_getKey($pasteid));
        return $o->exists();
    }

    /**
     * @inheritDoc
     */
    public function createComment($pasteid, $parentid, $commentid, array $comment)
    {
        if ($this->existsComment($pasteid, $parentid, $commentid)) {
            return false;
        }
        $key = $this->_getKey($pasteid) . '/discussion/' . $parentid . '/' . $commentid;
        return $this->_upload($key, $comment);
    }

    /**
     * @inheritDoc
     */
    public function readComments($pasteid)
    {
        $comments = array();
        $prefix   = $this->_getKey($pasteid) . '/discussion/';
        try {
            foreach (self::$_bucket->objects(array('prefix' => $prefix)) as $key) {
                $comment         = JSON::decode(self::$_bucket->object($key->name())->downloadAsString());
                $comment['id']   = basename($key->name());
                $slot            = $this->getOpenSlot($comments, (int) $comment['meta']['created']);
                $comments[$slot] = $comment;
            }
        } catch (NotFoundException $e) {
            // no comments found
        }
        return $comments;
    }

    /**
     * @inheritDoc
     */
    public function existsComment($pasteid, $parentid, $commentid)
    {
        $name = $this->_getKey($pasteid) . '/discussion/' . $parentid . '/' . $commentid;
        $o    = self::$_bucket->object($name);
        return $o->exists();
    }

    /**
     * @inheritDoc
     */
    public function purgeValues($namespace, $time)
    {
        $path = 'config/' . $namespace;
        try {
            foreach (self::$_bucket->objects(array('prefix' => $path)) as $object) {
                $name = $object->name();
                if (strlen($name) > strlen($path) && substr($name, strlen($path), 1) !== '/') {
                    continue;
                }
                $info = $object->info();
                if (key_exists('metadata', $info) && key_exists('value', $info['metadata'])) {
                    $value = $info['metadata']['value'];
                    if (is_numeric($value) && intval($value) < $time) {
                        try {
                            $object->delete();
                        } catch (NotFoundException $e) {
                            // deleted by another instance.
                        }
                    }
                }
            }
        } catch (NotFoundException $e) {
            // no objects in the bucket yet
        }
    }

    /**
     * For GoogleCloudStorage, the value will also be stored in the metadata for the
     * namespaces traffic_limiter and purge_limiter.
     * @inheritDoc
     */
    public function setValue($value, $namespace, $key = '')
    {
        if ($key === '') {
            $key = 'config/' . $namespace;
        } else {
            $key = 'config/' . $namespace . '/' . $key;
        }

        $metadata = array('namespace' => $namespace);
        if ($namespace != 'salt') {
            $metadata['value'] = strval($value);
        }
        try {
            self::$_bucket->upload($value, array(
                'name'          => $key,
                'chunkSize'     => 262144,
                'predefinedAcl' => 'private',
                'metadata'      => array(
                    'content-type' => 'application/json',
                    'metadata'     => $metadata,
                ),
            ));
        } catch (Exception $e) {
            error_log('failed to set key ' . $key . ' to ' . self::$_bucket->name() . ', ' .
                trim(preg_replace('/\s\s+/', ' ', $e->getMessage())));
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getValue($namespace, $key = '')
    {
        if ($key === '') {
            $key = 'config/' . $namespace;
        } else {
            $key = 'config/' . $namespace . '/' . $key;
        }
        try {
            $o = self::$_bucket->object($key);
            return $o->downloadAsString();
        } catch (NotFoundException $e) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    protected function _getExpiredPastes($batchsize)
    {
        $expired = array();

        $now    = time();
        $prefix = self::$_prefix;
        if ($prefix != '') {
            $prefix .= '/';
        }
        try {
            foreach (self::$_bucket->objects(array('prefix' => $prefix)) as $object) {
                $metadata = $object->info()['metadata'];
                if ($metadata != null && array_key_exists('expire_date', $metadata)) {
                    $expire_at = intval($metadata['expire_date']);
                    if ($expire_at != 0 && $expire_at < $now) {
                        array_push($expired, basename($object->name()));
                    }
                }

                if (count($expired) > $batchsize) {
                    break;
                }
            }
        } catch (NotFoundException $e) {
            // no objects in the bucket yet
        }
        return $expired;
    }
}
