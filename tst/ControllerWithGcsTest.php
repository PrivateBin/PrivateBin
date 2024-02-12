<?php

use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use PrivateBin\Data\GoogleCloudStorage;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Persistence\TrafficLimiter;

require_once 'ControllerTest.php';

class ControllerWithGcsTest extends ControllerTest
{
    private static $_client;
    private static $_bucket;
    private $_options = array();

    public static function setUpBeforeClass(): void
    {
        $httpClient = new Client(array('debug'=>false));
        $handler    = HttpHandlerFactory::build($httpClient);

        $name     = 'pb-';
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < 29; ++$i) {
            $name .= $alphabet[rand(0, strlen($alphabet) - 1)];
        }
        self::$_client = new StorageClientStub(array());
        self::$_bucket = self::$_client->createBucket($name);
    }

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        $this->_options = array(
            'bucket' => self::$_bucket->name(),
            'prefix' => 'pastes',
        );
        $this->_data    = new GoogleCloudStorage($this->_options);
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
            'class' => 'GoogleCloudStorage',
        );
        $options['model_options'] = $this->_options;
        Helper::createIniFile(CONF, $options);
    }
}
