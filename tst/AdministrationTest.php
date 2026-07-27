<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PrivateBin\Data\Filesystem;

class AdministrationTest extends TestCase
{
    private $_path;

    private $_store;

    public function setUp(): void
    {
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_administration';
        Helper::rmDir($this->_path);
        mkdir($this->_path);
        mkdir($this->_path . DIRECTORY_SEPARATOR . 'cfg');

        $options                         = parse_ini_file(CONF_SAMPLE, true);
        $options['model_options']['dir'] = $this->_path . DIRECTORY_SEPARATOR . 'data';
        Helper::createIniFile(
            $this->_path . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php',
            $options
        );

        $this->_store = new Filesystem($options['model_options']);
        $paste        = Helper::getPaste();
        $this->_store->create(Helper::getPasteId(), $paste);
        file_put_contents(
            $options['model_options']['dir'] . DIRECTORY_SEPARATOR .
            substr(Helper::getPasteId(), 0, 2) . DIRECTORY_SEPARATOR .
            substr(Helper::getPasteId(), 2, 2) . DIRECTORY_SEPARATOR .
            Helper::getPasteId() . '.php',
            Filesystem::PROTECTION_LINE . PHP_EOL . '{'
        );
    }

    public function tearDown(): void
    {
        Helper::rmDir($this->_path);
    }

    public function testStatisticsSkipsDamagedPastes()
    {
        [$exitCode, $output] = $this->runAdministration('--statistics');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString("Damaged:\t\t1", $output);
        $this->assertStringNotContainsString('Unsupported v1 paste', $output);
    }

    public function testDeleteV1PreservesDamagedPastes()
    {
        [$exitCode, $output] = $this->runAdministration('--delete-v1');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'Error reading document ' . Helper::getPasteId(),
            $output
        );
        $this->assertTrue($this->_store->exists(Helper::getPasteId()));
    }

    private function runAdministration($option)
    {
        $command = 'CONFIG_PATH=' .
            escapeshellarg($this->_path . DIRECTORY_SEPARATOR . 'cfg') . ' ' .
            escapeshellarg(PHP_BINARY) . ' ' .
            escapeshellarg(realpath(PATH . 'bin' . DIRECTORY_SEPARATOR . 'administration')) . ' ' .
            escapeshellarg($option) . ' 2>&1';
        exec($command, $output, $exitCode);

        return [$exitCode, implode(PHP_EOL, $output)];
    }
}
