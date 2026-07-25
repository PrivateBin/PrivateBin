<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Data\S3Storage;

class S3HeadResultStub
{
    public function get($name)
    {
        return $name === 'Metadata' ? ['expire_date' => '1'] : null;
    }
}

class S3PurgeClientStub
{
    public $deletedKeys = [];

    public function listObjects($options)
    {
        $contents = [];
        if ($options['Prefix'] === 'pastes/') {
            $contents = [
                ['Key' => 'pastes/aaaaaaaaaaaaaaaa'],
                ['Key' => 'pastes/bbbbbbbbbbbbbbbb'],
                ['Key' => 'pastes/cccccccccccccccc'],
            ];
        }
        return [
            'Contents'    => $contents,
            'IsTruncated' => false,
        ];
    }

    public function headObject($options)
    {
        return new S3HeadResultStub;
    }

    public function deleteObject($options)
    {
        $this->deletedKeys[] = $options['Key'];
    }
}

class S3StorageTest extends TestCase
{
    public function testPurgeHonorsBatchSizeAndDoesNotDuplicatePrefix()
    {
        $reflection = new ReflectionClass(S3Storage::class);
        $storage    = $reflection->newInstanceWithoutConstructor();
        $client     = new S3PurgeClientStub;

        foreach ([
            '_client' => $client,
            '_bucket' => 'bucket',
            '_prefix' => 'pastes',
        ] as $name => $value) {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($storage, $value);
        }

        $storage->purge(2);

        $this->assertSame([
            'pastes/aaaaaaaaaaaaaaaa',
            'pastes/bbbbbbbbbbbbbbbb',
        ], $client->deletedKeys);
    }
}
