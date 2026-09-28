<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PrivateBin\Data\Database;
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

    public function testStatisticsSkipsStructurallyDamagedPastes()
    {
        $this->overwritePaste('{"adata":[],"meta":"invalid"}');

        [$exitCode, $output] = $this->runAdministration('--statistics');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString("Damaged:\t\t1", $output);
        $this->assertStringNotContainsString('Unsupported v1 paste', $output);
    }

    public function testDeleteV1PreservesStructurallyDamagedPastes()
    {
        $this->overwritePaste('{"meta":[]}');

        [$exitCode, $output] = $this->runAdministration('--delete-v1');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'Error reading document ' . Helper::getPasteId(),
            $output
        );
        $this->assertTrue($this->_store->exists(Helper::getPasteId()));
    }

    public function testDeleteV1DeletesLegacyPastes()
    {
        $this->overwritePaste('{"data":"","meta":{}}');

        [$exitCode] = $this->runAdministration('--delete-v1');

        $this->assertSame(0, $exitCode);
        $this->assertFalse($this->_store->exists(Helper::getPasteId()));
    }

    public function testDeleteAllReportsRecordsThatRemain()
    {
        $store = $this->createUndeletableDatabasePaste(Helper::getPaste());

        [$exitCode, $output] = $this->runAdministration('--delete-all');

        $this->assertSame(7, $exitCode);
        $this->assertStringContainsString(Helper::getPasteId(), $output);
        $this->assertStringNotContainsString('All documents successfully deleted', $output);
        $this->assertTrue($store->exists(Helper::getPasteId()));
    }

    public function testDeleteV1ReportsRecordsThatRemain()
    {
        $store = $this->createUndeletableDatabasePaste(['data' => '', 'meta' => []]);

        [$exitCode, $output] = $this->runAdministration('--delete-v1');

        $this->assertSame(7, $exitCode);
        $this->assertStringContainsString(Helper::getPasteId(), $output);
        $this->assertStringNotContainsString('All unsupported legacy v1 documents successfully deleted', $output);
        $this->assertTrue($store->exists(Helper::getPasteId()));
    }

    public function testPurgeReportsRecordsThatRemain()
    {
        $store = $this->createUndeletableDatabasePaste(
            Helper::getPaste(['expire_date' => time() - 60])
        );

        [$exitCode, $output] = $this->runAdministration('--purge');

        $this->assertSame(7, $exitCode);
        $this->assertStringNotContainsString('purging of expired documents concluded', $output);
        $this->assertTrue($store->exists(Helper::getPasteId()));
    }

    public function testPurgeReportsBackendErrors()
    {
        $store = $this->createUndeletableDatabasePaste(
            Helper::getPaste(['expire_date' => time() - 60]),
            true
        );

        [$exitCode, $output] = $this->runAdministration('--purge');

        $this->assertSame(8, $exitCode);
        $this->assertStringContainsString('Error: purging documents failed', $output);
        $this->assertStringNotContainsString('purging of expired documents concluded', $output);
        $this->assertTrue($store->exists(Helper::getPasteId()));
    }

    private function createUndeletableDatabasePaste(array $paste, $throw = false)
    {
        $databasePath = $this->_path . DIRECTORY_SEPARATOR . 'administration-' .
            bin2hex(random_bytes(4)) . '.sq3';
        $options                   = parse_ini_file(CONF_SAMPLE, true);
        $options['model']['class'] = 'Database';
        $options['model_options']  = [
            'dsn' => 'sqlite:' . $databasePath,
            'usr' => '',
            'pwd' => '',
            'opt' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        ];
        Helper::createIniFile(
            $this->_path . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php',
            $options
        );

        $store = new Database($options['model_options']);
        $this->assertTrue($store->create(Helper::getPasteId(), $paste));
        $database = new PDO('sqlite:' . $databasePath);
        $database->exec(
            'CREATE TRIGGER keep_paste BEFORE DELETE ON paste ' .
            'BEGIN SELECT RAISE(' .
            ($throw ? 'ABORT, "deletion blocked"' : 'IGNORE') .
            '); END'
        );

        return $store;
    }

    private function overwritePaste($data)
    {
        file_put_contents(
            $this->_path . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR .
            substr(Helper::getPasteId(), 0, 2) . DIRECTORY_SEPARATOR .
            substr(Helper::getPasteId(), 2, 2) . DIRECTORY_SEPARATOR .
            Helper::getPasteId() . '.php',
            Filesystem::PROTECTION_LINE . PHP_EOL . $data
        );
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
