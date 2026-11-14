<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PrivateBin\Configuration;
use PrivateBin\Data\Filesystem;
use PrivateBin\Exception\TranslatedException;
use PrivateBin\Model\Paste;

class NonDeletingFilesystem extends Filesystem
{
    public function delete($pasteid)
    {
    }
}

class PasteDeleteTest extends TestCase
{
    private $_path;

    private $_store;

    public function setUp(): void
    {
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_paste_delete';
        Helper::rmDir($this->_path);
        $this->_store = new Filesystem(['dir' => $this->_path]);
        $paste        = Helper::getPaste();
        $this->_store->create(Helper::getPasteId(), $paste);
    }

    public function tearDown(): void
    {
        $this->_store->delete(Helper::getPasteId());
        Helper::rmDir($this->_path);
    }

    public function testSuccessfulDeletion()
    {
        $paste = $this->createPaste($this->_store);
        $paste->delete();

        $this->assertFalse($paste->exists());
    }

    public function testFailedDeletionIsReported()
    {
        $paste = $this->createPaste(new NonDeletingFilesystem(['dir' => $this->_path]));

        try {
            $paste->delete();
            $this->fail('failed deletion was reported as successful');
        } catch (TranslatedException $e) {
            $this->assertSame(77, $e->getCode());
        }
        $this->assertTrue($paste->exists());
    }

    private function createPaste(Filesystem $store)
    {
        $paste = new Paste(new Configuration, $store);
        $paste->setId(Helper::getPasteId());
        return $paste;
    }
}
