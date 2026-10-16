<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Data\S3Storage;

class S3CommentBodyStub
{
    private $_data;

    public function __construct($data)
    {
        $this->_data = $data;
    }

    public function getContents()
    {
        return $this->_data;
    }
}

class S3CommentClientStub
{
    private $_objects;

    public function __construct($pasteid, $parentid, $commentid, $comment, $prefix = '', $includeCorrupt = true)
    {
        $prefix         = empty($prefix) ? '' : $prefix . '/';
        $prefix .= $pasteid . '/discussion/' . $parentid . '/';
        $this->_objects = [
            $prefix . $commentid => json_encode($comment),
        ];
        if ($includeCorrupt) {
            $this->_objects[$prefix . 'ffffffffffffffff'] = '{';
            $this->_objects[$prefix . 'eeeeeeeeeeeeeeee'] = json_encode(['meta' => true]);
            $this->_objects[$prefix . 'invalid']          = json_encode($comment);
        }
    }

    public function listObjects($options)
    {
        return [
            'Contents' => array_map(
                function ($key) {
                    return ['Key' => $key];
                },
                array_keys($this->_objects)
            ),
            'IsTruncated' => false,
        ];
    }

    public function getObject($options)
    {
        return [
            'Body'     => new S3CommentBodyStub($this->_objects[$options['Key']]),
            'Metadata' => ['created' => '1'],
        ];
    }
}

class S3StorageTest extends TestCase
{
    public function setUp(): void
    {
        ini_set('error_log', stream_get_meta_data(tmpfile())['uri']);
    }

    private function getStorage($client, $prefix = '')
    {
        $reflection = new ReflectionClass(S3Storage::class);
        $storage    = $reflection->newInstanceWithoutConstructor();

        foreach ([
            '_client' => $client,
            '_bucket' => 'bucket',
            '_prefix' => $prefix,
        ] as $name => $value) {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($storage, $value);
        }
        return $storage;
    }

    public function testCorruptCommentsAreIgnored()
    {
        $pasteid   = Helper::getPasteId();
        $commentid = Helper::getCommentId();
        $client    = new S3CommentClientStub($pasteid, $pasteid, $commentid, Helper::getComment());
        $storage   = $this->getStorage($client);
        $comments  = $storage->readComments($pasteid);
        $this->assertCount(1, $comments);
        $this->assertSame($commentid, current($comments)['id']);
    }

    public function testCommentIdentifiersHonorStoragePrefix()
    {
        $pasteid   = Helper::getPasteId();
        $commentid = Helper::getCommentId();
        $client    = new S3CommentClientStub($pasteid, $pasteid, $commentid, Helper::getComment(), 'pastes', false);
        $storage   = $this->getStorage($client, 'pastes');
        $comment   = current($storage->readComments($pasteid));
        $this->assertSame($commentid, $comment['id']);
        $this->assertSame($pasteid, $comment['parentid']);
    }

    public function testInvalidCommentParentIsIgnored()
    {
        $pasteid = Helper::getPasteId();
        $client  = new S3CommentClientStub(
            $pasteid,
            'invalid',
            Helper::getCommentId(),
            Helper::getComment(),
            '',
            false
        );
        $storage = $this->getStorage($client);
        $this->assertSame([], $storage->readComments($pasteid));
    }

    public function testCommentParentComesFromObjectKey()
    {
        $pasteid = Helper::getPasteId();
        $parent  = 'eeeeeeeeeeeeeeee';
        $client  = new S3CommentClientStub(
            $pasteid,
            $parent,
            Helper::getCommentId(),
            Helper::getComment(),
            '',
            false
        );
        $storage = $this->getStorage($client);
        $comment = current($storage->readComments($pasteid));
        $this->assertSame($parent, $comment['parentid']);
    }
}
