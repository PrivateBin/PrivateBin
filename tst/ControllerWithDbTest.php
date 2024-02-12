<?php

use PrivateBin\Data\Database;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Persistence\TrafficLimiter;

require_once 'ControllerTest.php';

class ControllerWithDbTest extends ControllerTest
{
    private $_options = array(
        'usr' => null,
        'pwd' => null,
        'opt' => array(
            PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true,
        ),
    );

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        $this->_options['dsn'] = 'sqlite:' . $this->_path . DIRECTORY_SEPARATOR . 'tst.sq3';
        $this->_data           = new Database($this->_options);
        ServerSalt::setStore($this->_data);
        TrafficLimiter::setStore($this->_data);
        $this->reset();
    }

    public function reset()
    {
        parent::reset();
        // but then inject a db config
        $options          = parse_ini_file(CONF, true);
        $options['model'] = array(
            'class' => 'Database',
        );
        $options['model_options'] = $this->_options;
        Helper::createIniFile(CONF, $options);
    }
}
