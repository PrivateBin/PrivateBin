<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Data\S3Storage;

class S3BodyStub
{
    public $data = '';

    public function getContents()
    {
        return $this->data;
    }
}

class S3ClientStub
{
    public $body;

    public function __construct()
    {
        $this->body = new S3BodyStub;
    }

    public function getObject(array $options)
    {
        return ['Body' => $this->body];
    }
}

class S3StorageTest extends TestCase
{
    private $_client;
    private $_model;

    public function setUp(): void
    {
        $reflection    = new ReflectionClass(S3Storage::class);
        $this->_client = new S3ClientStub;
        $this->_model  = $reflection->newInstanceWithoutConstructor();
        foreach (['_client' => $this->_client, '_bucket' => 'bucket', '_prefix' => ''] as $name => $value) {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($this->_model, $value);
        }
    }

    public function testCorruptPastesAreRejected()
    {
        $paste                     = Helper::getPaste();
        $this->_client->body->data = json_encode($paste);
        $this->assertSame($paste, $this->_model->read(Helper::getPasteId()));

        $errorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');
        try {
            foreach ([true, [], ['meta' => true]] as $corruptPaste) {
                $this->_client->body->data = json_encode($corruptPaste);
                $this->assertFalse($this->_model->read(Helper::getPasteId()));
            }
        } finally {
            ini_set('error_log', $errorLog);
        }
    }
}
