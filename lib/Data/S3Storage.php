<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2022 Felix J. Ogris (https://ogris.de/)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 *
 * an S3 compatible data backend for PrivateBin with CEPH/RadosGW in mind
 * see https://docs.ceph.com/en/latest/radosgw/s3/php/
 *
 * Installation:
 *   1. Make sure you have composer.lock and composer.json in the document root of your PasteBin
 *   2. If not, grab a copy from https://github.com/PrivateBin/PrivateBin
 *   3. As non-root user, install the AWS SDK for PHP:
 *      composer require aws/aws-sdk-php
 *      (On FreeBSD, install devel/php-composer2 prior, e.g.: make -C /usr/ports/devel/php-composer2 install clean)
 *   4. In cfg/conf.php, comment out all [model] and [model_options] settings
 *   5. Still in cfg/conf.php, add a new [model] section:
 *      [model]
 *      class = S3Storage
 *   6. Add a new [model_options] as well, e.g. for a Rados gateway as part of your CEPH cluster:
 *      [model_options]
 *      region = ""
 *      version = "2006-03-01"
 *      endpoint = "https://s3.my-ceph.invalid"
 *      use_path_style_endpoint = true
 *      bucket = "my-bucket"
 *      prefix = "privatebin"  (place all PrivateBin data beneath this prefix)
 *      accesskey = "my-rados-user"
 *      secretkey = "my-rados-pass"
 */

namespace PrivateBin\Data;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use PrivateBin\Json;

class S3Storage extends AbstractData
{
    /**
     * S3 client
     *
     * @access private
     * @var    S3Client
     */
    private $_client = null;

    /**
     * S3 client options
     *
     * @access private
     * @var    array
     */
    private $_options = array();

    /**
     * S3 bucket
     *
     * @access private
     * @var    string
     */
    private $_bucket = null;

    /**
     * S3 prefix for all PrivateBin data in this bucket
     *
     * @access private
     * @var    string
     */
    private $_prefix = '';

    /**
     * instantiates a new S3 data backend.
     *
     * @access public
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (is_array($options)) {
            // AWS SDK will try to load credentials from environment if credentials are not passed via configuration
            // ref: https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html#default-credential-chain
            if (isset($options['accesskey']) && isset($options['secretkey'])) {
                $this->_options['credentials'] = array();

                $this->_options['credentials']['key']    = $options['accesskey'];
                $this->_options['credentials']['secret'] = $options['secretkey'];
            }
            if (array_key_exists('region', $options)) {
                $this->_options['region'] = $options['region'];
            }
            if (array_key_exists('version', $options)) {
                $this->_options['version'] = $options['version'];
            }
            if (array_key_exists('endpoint', $options)) {
                $this->_options['endpoint'] = $options['endpoint'];
            }
            if (array_key_exists('use_path_style_endpoint', $options)) {
                $this->_options['use_path_style_endpoint'] = filter_var($options['use_path_style_endpoint'], FILTER_VALIDATE_BOOLEAN);
            }
            if (array_key_exists('bucket', $options)) {
                $this->_bucket = $options['bucket'];
            }
            if (array_key_exists('prefix', $options)) {
                $this->_prefix = $options['prefix'];
            }
        }

        $this->_client = new S3Client($this->_options);
    }

    /**
     * returns all objects in the given prefix.
     *
     * @access private
     * @param $prefix string with prefix
     * @return array all objects in the given prefix
     */
    private function _listAllObjects($prefix)
    {
        $allObjects = array();
        $options    = array(
            'Bucket' => $this->_bucket,
            'Prefix' => $prefix,
        );

        do {
            $objectsListResponse = $this->_client->listObjects($options);
            $objects             = $objectsListResponse['Contents'] ?? array();
            foreach ($objects as $object) {
                $allObjects[]      = $object;
                $options['Marker'] = $object['Key'];
            }
        } while ($objectsListResponse['IsTruncated']);

        return $allObjects;
    }

    /**
     * returns the S3 storage object key for $pasteid in $this->_bucket.
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
     * as the S3 object's metadata except for the fields attachment, attachmentname
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
            $this->_client->putObject(array(
                'Bucket'      => $this->_bucket,
                'Key'         => $key,
                'Body'        => Json::encode($payload),
                'ContentType' => 'application/json',
                'Metadata'    => $metadata,
            ));
        } catch (S3Exception $e) {
            error_log('failed to upload ' . $key . ' to ' . $this->_bucket . ', ' .
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
            $object = $this->_client->getObject(array(
                'Bucket' => $this->_bucket,
                'Key'    => $this->_getKey($pasteid),
            ));
            $data = $object['Body']->getContents();
            return Json::decode($data);
        } catch (S3Exception $e) {
            error_log('failed to read ' . $pasteid . ' from ' . $this->_bucket . ', ' .
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
            $comments = $this->_listAllObjects($name . '/discussion/');
            foreach ($comments as $comment) {
                try {
                    $this->_client->deleteObject(array(
                        'Bucket' => $this->_bucket,
                        'Key'    => $comment['Key'],
                    ));
                } catch (S3Exception $e) {
                    // ignore if already deleted.
                }
            }
        } catch (S3Exception $e) {
            // there are no discussions associated with the paste
        }

        try {
            $this->_client->deleteObject(array(
                'Bucket' => $this->_bucket,
                'Key'    => $name,
            ));
        } catch (S3Exception $e) {
            // ignore if already deleted
        }
    }

    /**
     * @inheritDoc
     */
    public function exists($pasteid)
    {
        return $this->_client->doesObjectExistV2($this->_bucket, $this->_getKey($pasteid));
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
            $entries = $this->_listAllObjects($prefix);
            foreach ($entries as $entry) {
                $object = $this->_client->getObject(array(
                    'Bucket' => $this->_bucket,
                    'Key'    => $entry['Key'],
                ));
                $body             = JSON::decode($object['Body']->getContents());
                $items            = explode('/', $entry['Key']);
                $body['id']       = $items[3];
                $body['parentid'] = $items[2];
                $slot             = $this->getOpenSlot($comments, (int) $object['Metadata']['created']);
                $comments[$slot]  = $body;
            }
        } catch (S3Exception $e) {
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
        return $this->_client->doesObjectExistV2($this->_bucket, $name);
    }

    /**
     * @inheritDoc
     */
    public function purgeValues($namespace, $time)
    {
        $path = $this->_prefix;
        if ($path != '') {
            $path .= '/';
        }
        $path .= 'config/' . $namespace;

        try {
            foreach ($this->_listAllObjects($path) as $object) {
                $name = $object['Key'];
                if (strlen($name) > strlen($path) && substr($name, strlen($path), 1) !== '/') {
                    continue;
                }
                $head = $this->_client->headObject(array(
                    'Bucket' => $this->_bucket,
                    'Key'    => $name,
                ));
                if ($head->get('Metadata') != null && array_key_exists('value', $head->get('Metadata'))) {
                    $value = $head->get('Metadata')['value'];
                    if (is_numeric($value) && intval($value) < $time) {
                        try {
                            $this->_client->deleteObject(array(
                                'Bucket' => $this->_bucket,
                                'Key'    => $name,
                            ));
                        } catch (S3Exception $e) {
                            // deleted by another instance.
                        }
                    }
                }
            }
        } catch (S3Exception $e) {
            // no objects in the bucket yet
        }
    }

    /**
     * For S3, the value will also be stored in the metadata for the
     * namespaces traffic_limiter and purge_limiter.
     * @inheritDoc
     */
    public function setValue($value, $namespace, $key = '')
    {
        $prefix = $this->_prefix;
        if ($prefix != '') {
            $prefix .= '/';
        }

        if ($key === '') {
            $key = $prefix . 'config/' . $namespace;
        } else {
            $key = $prefix . 'config/' . $namespace . '/' . $key;
        }

        $metadata = array('namespace' => $namespace);
        if ($namespace != 'salt') {
            $metadata['value'] = strval($value);
        }
        try {
            $this->_client->putObject(array(
                'Bucket'      => $this->_bucket,
                'Key'         => $key,
                'Body'        => $value,
                'ContentType' => 'application/json',
                'Metadata'    => $metadata,
            ));
        } catch (S3Exception $e) {
            error_log('failed to set key ' . $key . ' to ' . $this->_bucket . ', ' .
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
        $prefix = $this->_prefix;
        if ($prefix != '') {
            $prefix .= '/';
        }

        if ($key === '') {
            $key = $prefix . 'config/' . $namespace;
        } else {
            $key = $prefix . 'config/' . $namespace . '/' . $key;
        }

        try {
            $object = $this->_client->getObject(array(
                'Bucket' => $this->_bucket,
                'Key'    => $key,
            ));
            return $object['Body']->getContents();
        } catch (S3Exception $e) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    protected function _getExpiredPastes($batchsize)
    {
        $expired = array();
        $now     = time();
        $prefix  = $this->_prefix;
        if ($prefix != '') {
            $prefix .= '/';
        }

        try {
            foreach ($this->_listAllObjects($prefix) as $object) {
                $head = $this->_client->headObject(array(
                    'Bucket' => $this->_bucket,
                    'Key'    => $object['Key'],
                ));
                if ($head->get('Metadata') != null && array_key_exists('expire_date', $head->get('Metadata'))) {
                    $expire_at = intval($head->get('Metadata')['expire_date']);
                    if ($expire_at != 0 && $expire_at < $now) {
                        array_push($expired, $object['Key']);
                    }
                }

                if (count($expired) > $batchsize) {
                    break;
                }
            }
        } catch (S3Exception $e) {
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
            foreach ($this->_listAllObjects($prefix) as $object) {
                $candidate = substr($object['Key'], strlen($prefix));
                if (!str_contains($candidate, '/')) {
                    $pastes[] = $candidate;
                }
            }
        } catch (S3Exception $e) {
            // no objects in the bucket yet
        }
        return $pastes;
    }
}
