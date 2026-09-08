<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class AdministrationEmptyDirsTest extends TestCase
{
    private $_path;

    public function setUp(): void
    {
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin administration';
        Helper::rmDir($this->_path);
        mkdir($this->_path);
        mkdir($this->_path . DIRECTORY_SEPARATOR . 'cfg');

        $dataPath = $this->_path . DIRECTORY_SEPARATOR . 'data with spaces';
        mkdir($dataPath);
        mkdir($dataPath . DIRECTORY_SEPARATOR . 'empty' . DIRECTORY_SEPARATOR . 'nested', 0777, true);
        file_put_contents($dataPath . DIRECTORY_SEPARATOR . 'keep.txt', 'keep');

        $options                         = parse_ini_file(CONF_SAMPLE, true);
        $options['model_options']['dir'] = $dataPath;
        Helper::createIniFile(
            $this->_path . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php',
            $options
        );
    }

    public function tearDown(): void
    {
        Helper::rmDir($this->_path);
    }

    public function testEmptyDirsSupportsStoragePathsWithSpaces()
    {
        $command = 'CONFIG_PATH=' .
            escapeshellarg($this->_path . DIRECTORY_SEPARATOR . 'cfg') . ' ' .
            escapeshellarg(PHP_BINARY) . ' ' .
            escapeshellarg(realpath(PATH . 'bin' . DIRECTORY_SEPARATOR . 'administration')) .
            ' --empty-dirs 2>&1';
        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
        $this->assertDirectoryDoesNotExist(
            $this->_path . DIRECTORY_SEPARATOR . 'data with spaces' .
            DIRECTORY_SEPARATOR . 'empty'
        );
        $this->assertFileExists(
            $this->_path . DIRECTORY_SEPARATOR . 'data with spaces' .
            DIRECTORY_SEPARATOR . 'keep.txt'
        );
    }
}
