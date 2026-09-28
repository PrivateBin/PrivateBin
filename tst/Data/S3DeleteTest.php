<?php declare(strict_types=1);

namespace Aws\S3\Exception {
    if (!class_exists(S3Exception::class)) {
        class S3Exception extends \Exception
        {
            const TEST_STUB = true;

            private $_awsErrorCode;

            public function __construct($awsErrorCode = null)
            {
                $this->_awsErrorCode = $awsErrorCode;
            }

            public function getAwsErrorCode()
            {
                return $this->_awsErrorCode;
            }
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

    class S3ValueClientStub
    {
        private $_exception;

        public function __construct(S3Exception $exception)
        {
            $this->_exception = $exception;
        }

        public function getObject($options)
        {
            throw $this->_exception;
        }
    }

    class S3ListClientStub
    {
        private $_exception;

        public function __construct(S3Exception $exception)
        {
            $this->_exception = $exception;
        }

        public function listObjects($options)
        {
            throw $this->_exception;
        }
    }

    class S3CommentReadClientStub
    {
        private $_exception;

        public function __construct(S3Exception $exception)
        {
            $this->_exception = $exception;
        }

        public function listObjects($options)
        {
            return [
                'Contents'    => [[
                    'Key' => Helper::getPasteId() . '/discussion/' .
                        Helper::getPasteId() . '/' . Helper::getCommentId(),
                ]],
                'IsTruncated' => false,
            ];
        }

        public function getObject($options)
        {
            throw $this->_exception;
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

        public function testValueReadErrorsArePropagated()
        {
            $storage = $this->getStorage(
                new S3ValueClientStub($this->getS3Exception('AccessDenied'))
            );

            $failed = false;
            try {
                $storage->getValue('salt');
            } catch (S3Exception $e) {
                $failed = true;
            }

            $this->assertTrue($failed);
        }

        public function testMissingValuesRemainEmpty()
        {
            $storage = $this->getStorage(
                new S3ValueClientStub($this->getS3Exception('NoSuchKey'))
            );

            $this->assertSame('', $storage->getValue('salt'));
        }

        public function testPasteReadErrorsArePropagated()
        {
            $storage = $this->getStorage(
                new S3ValueClientStub($this->getS3Exception('AccessDenied'))
            );

            $failed = false;
            try {
                $storage->read(Helper::getPasteId());
            } catch (S3Exception $e) {
                $failed = true;
            }

            $this->assertTrue($failed);
        }

        public function testMissingPastesRemainMissing()
        {
            $storage = $this->getStorage(
                new S3ValueClientStub($this->getS3Exception('NoSuchKey'))
            );

            $this->assertFalse($storage->read(Helper::getPasteId()));
        }

        public function testListErrorsArePropagated()
        {
            $storage = $this->getStorage(
                new S3ListClientStub($this->getS3Exception('AccessDenied'))
            );

            foreach ([
                function () use ($storage) {
                    $storage->getAllPastes();
                },
                function () use ($storage) {
                    $storage->readComments(Helper::getPasteId());
                },
                function () use ($storage) {
                    $storage->purgeValues('traffic_limiter', time());
                },
                function () use ($storage) {
                    $storage->purge(1);
                },
            ] as $operation) {
                $failed = false;
                try {
                    $operation();
                } catch (S3Exception $e) {
                    $failed = true;
                }
                $this->assertTrue($failed);
            }
        }

        public function testCommentReadErrorsArePropagated()
        {
            $storage = $this->getStorage(
                new S3CommentReadClientStub($this->getS3Exception('AccessDenied'))
            );

            $failed = false;
            try {
                $storage->readComments(Helper::getPasteId());
            } catch (S3Exception $e) {
                $failed = true;
            }

            $this->assertTrue($failed);
        }

        public function testCommentsDeletedDuringReadAreIgnored()
        {
            $storage = $this->getStorage(
                new S3CommentReadClientStub($this->getS3Exception('NoSuchKey'))
            );

            $this->assertSame([], $storage->readComments(Helper::getPasteId()));
        }

        private function getS3Exception($awsErrorCode)
        {
            if (defined(S3Exception::class . '::TEST_STUB')) {
                return new S3Exception($awsErrorCode);
            }
            $exception = $this->getMockBuilder(S3Exception::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getAwsErrorCode'])
                ->getMock();
            $exception->method('getAwsErrorCode')->willReturn($awsErrorCode);
            return $exception;
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
