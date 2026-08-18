<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PrivateBin\Data\Filesystem;

class MigrateForceOverwriteTest extends TestCase
{
    private $_destination;

    private $_path;

    private $_source;

    public function setUp(): void
    {
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_migrate_force';
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

        $options['model_options']['dir'] = $this->_path . DIRECTORY_SEPARATOR . 'destination';
        $this->_destination              = new Filesystem($options['model_options']);
        Helper::createIniFile(
            $this->_path . DIRECTORY_SEPARATOR . 'destination_cfg' . DIRECTORY_SEPARATOR . 'conf.php',
            $options
        );
    }

    public function tearDown(): void
    {
        Helper::rmDir($this->_path);
    }

    public function testForceReplacesPasteAndDiscussion()
    {
        $sourcePaste       = Helper::getPaste();
        $sourcePaste['ct'] = 'source paste';
        $this->_source->create(Helper::getPasteId(), $sourcePaste);
        $sourceComment       = Helper::getComment();
        $sourceComment['ct'] = 'source comment';
        $this->_source->createComment(
            Helper::getPasteId(),
            Helper::getPasteId(),
            Helper::getCommentId(),
            $sourceComment
        );

        $destinationPaste       = Helper::getPaste();
        $destinationPaste['ct'] = 'old paste';
        $this->_destination->create(Helper::getPasteId(), $destinationPaste);
        $destinationComment       = Helper::getComment();
        $destinationComment['ct'] = 'old comment';
        $this->_destination->createComment(
            Helper::getPasteId(),
            Helper::getPasteId(),
            Helper::getCommentId(),
            $destinationComment
        );

        $command = escapeshellarg(PHP_BINARY) . ' ' .
            escapeshellarg(realpath(PATH . 'bin' . DIRECTORY_SEPARATOR . 'migrate')) .
            ' -f --delete-after ' .
            escapeshellarg($this->_path . DIRECTORY_SEPARATOR . 'source_cfg') . ' ' .
            escapeshellarg($this->_path . DIRECTORY_SEPARATOR . 'destination_cfg') .
            ' 2>&1';
        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
        $this->assertFalse($this->_source->exists(Helper::getPasteId()));
        $this->assertSame(
            'source paste',
            $this->_destination->read(Helper::getPasteId())['ct']
        );
        $comments = array_values($this->_destination->readComments(Helper::getPasteId()));
        $this->assertCount(1, $comments);
        $this->assertSame('source comment', $comments[0]['ct']);
    }
}
