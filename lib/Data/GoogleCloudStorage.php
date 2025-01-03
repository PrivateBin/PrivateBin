<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

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
     * @var    StorageClient
     */
    private $_client = null;

    /**
     * GCS bucket
     *
     * @access private
     * @var    Bucket
     */
    private $_bucket = null;

    /**
     * object prefix
     *
     * @access private
     * @var    string
     */
    private $_prefix = 'pastes';

    /**
     * bucket acl type
     *
     * @access private
     * @var    bool
     */
    private $_uniformacl = false;

    /**
     * instantiantes a new Google Cloud Storage data backend.
     *
     * @access public
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (getenv('PRIVATEBIN_GCS_BUCKET')) {
            $bucket = getenv('PRIVATEBIN_GCS_BUCKET');
        }
        if (is_array($options) && array_key_exists('bucket', $options)) {
            $bucket = $options['bucket'];
        }
        if (is_array($options) && array_key_exists('prefix', $options)) {
            $this->_prefix = $options['prefix'];
        }
        if (is_array($options) && array_key_exists('uniformacl', $options)) {
            $this->_uniformacl = $options['uniformacl'];
        }

        $this->_client = class_exists('StorageClientStub', false) ?
            new \StorageClientStub(array()) :
            new StorageClient(array('suppressKeyFileNotice' => true));
        if (isset($bucket)) {
            $this->_bucket = $this->_client->bucket($bucket);
        }
    }

    /**
     * returns the google storage object key for $pasteid in $this->_bucket.
     *
     * @access private
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
    private function _upload($key, $payload)
    {
        $metadata = array_key_exists('meta', $payload) ? $payload['meta'] : array();
        unset($metadata['attachment'], $metadata['attachmentname'], $metadata['salt']);
        foreach ($metadata as $k => $v) {
            $metadata[$k] = strval($v);
        }
        try {
            $data = array(
                'name'          => $key,
                'chunkSize'     => 262144,
                'metadata'      => array(
                    'content-type' => 'application/json',
                    'metadata'     => $metadata,
                ),
            );
            if (!$this->_uniformacl) {
                $data['predefinedAcl'] = 'private';
            }
            $this->_bucket->upload(Json::encode($payload), $data);
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

        return $this->_upload($this->_getKey($pasteid), $paste);
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
     * @inheritDoc
     */
    public function purgeValues($namespace, $time)
    {
        $path = 'config/' . $namespace;
        try {
            foreach ($this->_bucket->objects(array('prefix' => $path)) as $object) {
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
            $data = array(
                'name'          => $key,
                'chunkSize'     => 262144,
                'metadata'      => array(
                    'content-type' => 'application/json',
                    'metadata'     => $metadata,
                ),
            );
            if (!$this->_uniformacl) {
                $data['predefinedAcl'] = 'private';
            }
            $this->_bucket->upload($value, $data);
        } catch (Exception $e) {
            error_log('failed to set key ' . $key . ' to ' . $this->_bucket->name() . ', ' .
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
            $o = $this->_bucket->object($key);
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
        $prefix = $this->_prefix;
        if ($prefix != '') {
            $prefix .= '/';
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

    /**
     * @inheritDoc
     */
    public function getAllPastes()
    {
        $pastes = array();
        $prefix = $this->_prefix;
        if ($prefix != '') {
            $prefix .= '/';
        }

        try {
            foreach ($this->_bucket->objects(array('prefix' => $prefix)) as $object) {
                $candidate = substr($object->name(), strlen($prefix));
                if (!str_contains($candidate, '/')) {
                    $pastes[] = $candidate;
                }
            }
        } catch (NotFoundException $e) {
            // no objects in the bucket yet
        }
        return $pastes;
    }
}
