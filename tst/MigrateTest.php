<?php
use PHPUnit\Framework\TestCase;
use PrivateBin\Data\Database;
use PrivateBin\Data\Filesystem;

class MigrateTest extends TestCase
{
    protected $_model_1;

    protected $_model_2;

    protected $_path;

    protected $_path_instance_1;

    protected $_path_instance_2;

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path            = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        $this->_path_instance_1 = $this->_path . DIRECTORY_SEPARATOR . 'instance_1';
        $this->_path_instance_2 = $this->_path . DIRECTORY_SEPARATOR . 'instance_2';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        mkdir($this->_path_instance_1);
        mkdir($this->_path_instance_1 . DIRECTORY_SEPARATOR . 'cfg');
        mkdir($this->_path_instance_2);
        mkdir($this->_path_instance_2 . DIRECTORY_SEPARATOR . 'cfg');
        $options                         = parse_ini_file(CONF_SAMPLE, true);
        $options['purge']['limit']       = 0;
        $options['model_options']['dir'] = $this->_path_instance_1 . DIRECTORY_SEPARATOR . 'data';
        $this->_model_1                  = new Filesystem($options['model_options']);
        Helper::createIniFile($this->_path_instance_1 . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php', $options);

        $options['model']          = array(
            'class' => 'Database',
        );
        $options['model_options'] = array(
            'dsn' => 'sqlite:' . $this->_path_instance_2 . DIRECTORY_SEPARATOR . 'test.sq3',
            'usr' => null,
            'pwd' => null,
            'opt' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
        );
        $this->_model_2 = new Database($options['model_options']);
        Helper::createIniFile($this->_path_instance_2 . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php', $options);
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        Helper::rmDir($this->_path);
    }

    public function testMigrate()
    {
        $this->_model_1->delete(Helper::getPasteId());
        $this->_model_2->delete(Helper::getPasteId());

        // storing paste & comment
        $this->_model_1->create(Helper::getPasteId(), Helper::getPaste());
        $this->_model_1->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), Helper::getComment());

        // migrate files to database
        $output    = null;
        $exit_code = 255;
        exec('php ' . PATH . 'bin' . DIRECTORY_SEPARATOR . 'migrate --delete-after ' . $this->_path_instance_1 . DIRECTORY_SEPARATOR . 'cfg ' . $this->_path_instance_2 . DIRECTORY_SEPARATOR . 'cfg', $output, $exit_code);
        $this->assertEquals(0, $exit_code, 'migrate script exits 0');
        $this->assertFalse($this->_model_1->exists(Helper::getPasteId()), 'paste removed after migrating it');
        $this->assertFalse($this->_model_1->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment removed after migrating it');
        $this->assertTrue($this->_model_2->exists(Helper::getPasteId()), 'paste migrated');
        $this->assertTrue($this->_model_2->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment migrated');

        // migrate back to files
        $exit_code = 255;
        exec('php ' . PATH . 'bin' . DIRECTORY_SEPARATOR . 'migrate ' . $this->_path_instance_2 . DIRECTORY_SEPARATOR . 'cfg ' . $this->_path_instance_1 . DIRECTORY_SEPARATOR . 'cfg', $output, $exit_code);
        $this->assertEquals(0, $exit_code, 'migrate script exits 0');
        $this->assertTrue($this->_model_1->exists(Helper::getPasteId()), 'paste migrated back');
        $this->assertTrue($this->_model_1->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment migrated back');
    }
}
