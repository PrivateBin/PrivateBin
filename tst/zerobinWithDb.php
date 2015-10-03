<?php
require_once 'zerobin.php';

class zerobinWithDbTest extends zerobinTest
{
    private $_options = array(
        'dsn' => 'sqlite:../data/tst.sq3',
        'usr' => null,
        'pwd' => null,
        'opt' => array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true
        ),
    );

    public function setUp()
    {
        /* Setup Routine */
        $this->_model = zerobin_db::getInstance($this->_options);
        serversalt::setPath(PATH . 'data');
        $this->reset();
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        parent::tearDown();
        @unlink('../data/tst.sq3');
    }

    public function reset()
    {
        parent::reset();
        // but then inject a db config
        $options = parse_ini_file(CONF, true);
        $options['model'] = array(
            'class' => 'zerobin_db',
        );
        $options['model_options'] = $this->_options;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
    }
}