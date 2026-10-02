<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PrivateBin\Data\Filesystem;

class MigrateWriteFailureTest extends TestCase
{
    private $_path;

    private $_destinationPath;

    private $_source;

    public function setUp(): void
    {
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_migrate_write_failure';
        Helper::rmDir($this->_path);
        mkdir($this->_path);
        mkdir($this->_path . DIRECTORY_SEPARATOR . 'source_cfg');
        mkdir($this->_path . DIRECTORY_SEPARATOR . 'destination_cfg');

        $options                         = parse_ini_file(CONF_SAMPLE, true);
        $options['model_options']['dir'] = $this->_path . DIRECTORY_SEPARATOR . 'source';
        $this->_source                   = new Filesystem($options['model_options']);
        Helper::createIniFile(
            $this->_path . DIRECTORY_SEPARATOR . 'source_cfg' . DIRECTORY_SEPARATOR . 'conf.php',
            $options
        );

        $this->_destinationPath = $this->_path . DIRECTORY_SEPARATOR . 'blocked';
        file_put_contents($this->_destinationPath, 'not a directory');
        $options['model_options']['dir'] = $this->_destinationPath;
        Helper::createIniFile(
            $this->_path . DIRECTORY_SEPARATOR . 'destination_cfg' . DIRECTORY_SEPARATOR . 'conf.php',
            $options
        );
    }

    public function tearDown(): void
    {
        Helper::rmDir($this->_path);
    }

    public function testSourceIsPreservedWhenDestinationWriteFails()
    {
        $paste = Helper::getPaste();
        $this->_source->create(Helper::getPasteId(), $paste);

        [$exitCode, $output] = $this->runMigration();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString(
            'ERROR: Unable to save document ID ' . Helper::getPasteId(),
            $output
        );
        $this->assertTrue($this->_source->exists(Helper::getPasteId()));
    }

    public function testSourceIsPreservedWhenDestinationCommentWriteFails()
    {
        unlink($this->_destinationPath);
        mkdir(
            $this->_destinationPath . DIRECTORY_SEPARATOR .
            substr(Helper::getPasteId(), 0, 2) . DIRECTORY_SEPARATOR .
            substr(Helper::getPasteId(), 2, 2),
            0777,
            true
        );
        file_put_contents(
            $this->_destinationPath . DIRECTORY_SEPARATOR .
            substr(Helper::getPasteId(), 0, 2) . DIRECTORY_SEPARATOR .
            substr(Helper::getPasteId(), 2, 2) . DIRECTORY_SEPARATOR .
            Helper::getPasteId() . '.discussion',
            'not a directory'
        );

        $paste = Helper::getPaste();
        $this->_source->create(Helper::getPasteId(), $paste);
        $comment = Helper::getComment();
        $this->_source->createComment(
            Helper::getPasteId(),
            Helper::getPasteId(),
            Helper::getCommentId(),
            $comment
        );

        [$exitCode, $output] = $this->runMigration();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString(
            'ERROR: Unable to save document ID ' . Helper::getPasteId() .
            ', parent id ' . Helper::getPasteId() .
            ', comment id ' . Helper::getCommentId(),
            $output
        );
        $this->assertTrue($this->_source->exists(Helper::getPasteId()));
        $this->assertTrue($this->_source->existsComment(
            Helper::getPasteId(),
            Helper::getPasteId(),
            Helper::getCommentId()
        ));
    }

    private function runMigration()
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' .
            escapeshellarg(realpath(PATH . 'bin' . DIRECTORY_SEPARATOR . 'migrate')) .
            ' --delete-after ' .
            escapeshellarg($this->_path . DIRECTORY_SEPARATOR . 'source_cfg') . ' ' .
            escapeshellarg($this->_path . DIRECTORY_SEPARATOR . 'destination_cfg') .
            ' 2>&1';
        exec($command, $output, $exitCode);

        return [$exitCode, implode(PHP_EOL, $output)];
    }
}
