<?php declare(strict_types=1);

namespace Aws\S3\Exception {
    if (!class_exists(S3Exception::class)) {
        class S3Exception extends \Exception
        {
        }
    }
}

namespace {
    use Aws\S3\Exception\S3Exception;
    use PHPUnit\Framework\TestCase;
    use PrivateBin\Data\S3Storage;

    class S3DeleteClientStub
    {
        public $deleted = [];

        private $_comment;

        private $_failKey;

        public function __construct($pasteid, $failKey, $includeComment)
        {
            $this->_comment = $pasteid . '/discussion/' . $pasteid . '/' . Helper::getCommentId();
            $this->_failKey = $failKey;
            if (!$includeComment) {
                $this->_comment = null;
            }
        }

        public function listObjects($options)
        {
            return [
                'Contents'    => $this->_comment === null ? [] : [['Key' => $this->_comment]],
                'IsTruncated' => false,
            ];
        }

        public function deleteObject($options)
        {
            if ($options['Key'] === $this->_failKey) {
                $reflection = new \ReflectionClass(S3Exception::class);
                throw $reflection->newInstanceWithoutConstructor();
            }
            $this->deleted[] = $options['Key'];
        }
    }

    class S3DeleteTest extends TestCase
    {
        public function testCommentDeletionErrorsStopPasteDeletion()
        {
            $pasteid = Helper::getPasteId();
            $comment = $pasteid . '/discussion/' . $pasteid . '/' . Helper::getCommentId();
            $client  = new S3DeleteClientStub($pasteid, $comment, true);
            $storage = $this->getStorage($client);

            $failed = false;
            try {
                $storage->delete($pasteid);
            } catch (S3Exception $e) {
                $failed = true;
            }

            $this->assertTrue($failed);
            $this->assertNotContains($pasteid, $client->deleted);
        }

        public function testPasteDeletionErrorsArePropagated()
        {
            $pasteid = Helper::getPasteId();
            $client  = new S3DeleteClientStub($pasteid, $pasteid, false);
            $storage = $this->getStorage($client);

            $failed = false;
            try {
                $storage->delete($pasteid);
            } catch (S3Exception $e) {
                $failed = true;
            }

            $this->assertTrue($failed);
        }

        private function getStorage($client)
        {
            $reflection = new \ReflectionClass(S3Storage::class);
            $storage    = $reflection->newInstanceWithoutConstructor();

            foreach ([
                '_bucket' => 'bucket',
                '_client' => $client,
                '_prefix' => '',
            ] as $property => $value) {
                $property = $reflection->getProperty($property);
                $property->setAccessible(true);
                $property->setValue($storage, $value);
            }

            return $storage;
        }
    }
}
