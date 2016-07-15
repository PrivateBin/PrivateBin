<?php
require_once 'privatebin.php';

class privatebinWithDbTest extends privatebinTest
{
    private $_options = array(
        'usr' => null,
        'pwd' => null,
        'opt' => array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true
        ),
    );

    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if(!is_dir($this->_path)) mkdir($this->_path);
        $this->_options['dsn'] = 'sqlite:' . $this->_path . '/tst.sq3';
        $this->_model = privatebin_db::getInstance($this->_options);
        $this->reset();
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        parent::tearDown();
        helper::rmdir($this->_path);
    }

    public function reset()
    {
        parent::reset();
        // but then inject a db config
        $options = parse_ini_file(CONF, true);
        $options['model'] = array(
            'class' => 'privatebin_db',
        );
        $options['model_options'] = $this->_options;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
    }
}
