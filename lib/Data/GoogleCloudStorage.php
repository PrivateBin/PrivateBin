<?php

namespace PrivateBin\Data;

use Exception;
use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\StorageClient;
use PrivateBin\Json;

class GoogleCloudStorage extends AbstractData
{
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
        $client = null;
        $bucket = null;
        $prefix = 'pastes';

        if (getenv('PRIVATEBIN_GCS_BUCKET')) {
            $bucket = getenv('PRIVATEBIN_GCS_BUCKET');
        }
        if (is_array($options) && array_key_exists('bucket', $options)) {
            $bucket = $options['bucket'];
        }
        if (is_array($options) && array_key_exists('prefix', $options)) {
            $prefix = $options['prefix'];
        }
        if (is_array($options) && array_key_exists('client', $options)) {
            $client = $options['client'];
        }

        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($bucket, $prefix, $client);
        }
        return self::$_instance;
    }

    protected $_client = null;
    protected $_bucket = null;
    protected $_prefix = 'pastes';

    public function __construct($bucket, $prefix, $client = null)
    {
        parent::__construct();
        if ($client == null) {
            $this->_client = new StorageClient(array('suppressKeyFileNotice' => true));
        } else {
            // use given client for test purposes
            $this->_client = $client;
        }

        $this->_bucket = $this->_client->bucket($bucket);
        if ($prefix != null) {
            $this->_prefix = $prefix;
        }
    }

    /**
     * returns the google storage object key for $pasteid in $this->_bucket.
     * @param $pasteid string to get the key for
     * @return string
     */
    private function _getKey($pasteid)
    {
        if ($this->_prefix != '') {
            return $this->_prefix . '/' . $pasteid;
        }
        return $pasteid;
    }

    /**
     * Uploads the payload in the $this->_bucket under the specified key.
     * The entire payload is stored as a JSON document. The metadata is replicated
     * as the GCS object's metadata except for the fields attachment, attachmentname
     * and salt.
     *
     * @param $key string to store the payload under
     * @param $payload array to store
     * @return bool true if successful, otherwise false.
     */
    private function upload($key, $payload)
    {
        $metadata = array_key_exists('meta', $payload) ? $payload['meta'] : array();
        unset($metadata['attachment'], $metadata['attachmentname'], $metadata['salt']);
        foreach ($metadata as $k => $v) {
            $metadata[$k] = strval($v);
        }
        try {
            $this->_bucket->upload(Json::encode($payload), array(
                'name'          => $key,
                'chunkSize'     => 262144,
                'predefinedAcl' => 'private',
                'metadata'      => array(
                    'content-type' => 'application/json',
                    'metadata'     => $metadata,
                ),
            ));
        } catch (Exception $e) {
            error_log('failed to upload ' . $key . ' to ' . $this->_bucket->name() . ', ' .
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

        return $this->upload($this->_getKey($pasteid), $paste);
    }

    /**
     * @inheritDoc
     */
    public function read($pasteid)
    {
        try {
            $o    = $this->_bucket->object($this->_getKey($pasteid));
            $data = $o->downloadAsString();
            return Json::decode($data);
        } catch (NotFoundException $e) {
            return false;
        } catch (Exception $e) {
            error_log('failed to read ' . $pasteid . ' from ' . $this->_bucket->name() . ', ' .
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
            foreach ($this->_bucket->objects(array('prefix' => $name . '/discussion/')) as $comment) {
                try {
                    $this->_bucket->object($comment->name())->delete();
                } catch (NotFoundException $e) {
                    // ignore if already deleted.
                }
            }
        } catch (NotFoundException $e) {
            // there are no discussions associated with the paste
        }

        try {
            $this->_bucket->object($name)->delete();
        } catch (NotFoundException $e) {
            // ignore if already deleted
        }
    }

    /**
     * @inheritDoc
     */
    public function exists($pasteid)
    {
        $o = $this->_bucket->object($this->_getKey($pasteid));
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
        return $this->upload($key, $comment);
    }

    /**
     * @inheritDoc
     */
    public function readComments($pasteid)
    {
        $comments = array();
        $prefix   = $this->_getKey($pasteid) . '/discussion/';
        try {
            foreach ($this->_bucket->objects(array('prefix' => $prefix)) as $key) {
                $comment         = JSON::decode($this->_bucket->object($key->name())->downloadAsString());
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
        $o    = $this->_bucket->object($name);
        return $o->exists();
    }

    /**
     * Purge outdated entries.
     *
     * @access public
     * @param  string $namespace
     * @param  int $time
     * @return void
     */
    public function purgeValues($namespace, $time)
    {
        switch ($namespace) {
            case 'traffic_limiter':
                ;
                break;
        }
    }

    /**
     * This is the simplest thing that could possibly work.
     * will be to tested for runtime performance.
     * @inheritDoc
     */
    public function setValue($value, $namespace, $key = '')
    {
        $key  = 'config/' . $namespace . '/' . $key;
        $data = Json::encode($value);

        try {
            $this->_bucket->upload($data, array(
                'name'          => $key,
                'chunkSize'     => 262144,
                'predefinedAcl' => 'private',
                'metadata'      => array(
                    'content-type' => 'application/json',
                    'metadata'     => array('namespace' => $namespace),
                ),
            ));
        } catch (Exception $e) {
            error_log('failed to set key ' . $key . ' to ' . $this->_bucket->name() . ', ' .
                trim(preg_replace('/\s\s+/', ' ', $e->getMessage())));
            return false;
        }
        return true;
    }

    /**
     * This is the simplest thing that could possibly work.
     * will be to tested for runtime performance.
     * @inheritDoc
     */
    public function getValue($namespace, $key = '')
    {
        $key = 'config/' . $namespace . '/' . $key;
        try {
            $o    = $this->_bucket->object($key);
            $data = $o->downloadAsString();
            return Json::decode($data);
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    protected function _getExpiredPastes($batchsize)
    {
        $expired = array();

        $now    = time();
        $prefix = $this->_prefix;
        if ($prefix != '') {
            $prefix = $prefix . '/';
        }
        try {
            foreach ($this->_bucket->objects(array('prefix' => $prefix)) as $object) {
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
