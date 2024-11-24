<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Configuration;

class ConfigurationTest extends TestCase
{
    private $_minimalConfig;

    private $_options;

    private $_path;

    public function setUp(): void
    {
        /* Setup Routine */
        Helper::confBackup();
        $this->_minimalConfig                   = '[main]' . PHP_EOL . '[model]' . PHP_EOL . '[model_options]';
        $this->_options                         = Configuration::getDefaults();
        $this->_options['model_options']['dir'] = PATH . $this->_options['model_options']['dir'];
        $this->_path                            = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_cfg';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        Helper::rmDir($this->_path);
        if (is_file(CONF)) {
            unlink(CONF);
        }
        Helper::confRestore();
    }

    public function testDefaultConfigFile()
    {
        $conf = new Configuration;
        $this->assertEquals($this->_options, $conf->get(), 'default configuration is correct');
    }

    public function testHandleFreshConfigFile()
    {
        Helper::createIniFile(CONF, $this->_options);
        $conf = new Configuration;
        $this->assertEquals($this->_options, $conf->get(), 'newly generated configuration is correct');
    }

    public function testHandleMissingConfigFile()
    {
        if (is_file(CONF)) {
            unlink(CONF);
        }
        $conf = new Configuration;
        $this->assertEquals($this->_options, $conf->get(), 'returns correct defaults on missing file');
    }

    public function testHandleBlankConfigFile()
    {
        file_put_contents(CONF, '');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(2);
        new Configuration;
    }

    public function testHandleMinimalConfigFile()
    {
        file_put_contents(CONF, $this->_minimalConfig);
        $conf = new Configuration;
        $this->assertEquals($this->_options, $conf->get(), 'returns correct defaults on empty file');
    }

    public function testHandleInvalidSection()
    {
        file_put_contents(CONF, $this->_minimalConfig);
        $conf = new Configuration;
        $this->expectException(Exception::class);
        $this->expectExceptionCode(3);
        $conf->getKey('foo', 'bar');
    }

    public function testHandleInvalidKey()
    {
        file_put_contents(CONF, $this->_minimalConfig);
        $conf = new Configuration;
        $this->expectException(Exception::class);
        $this->expectExceptionCode(4);
        $conf->getKey('foo');
    }

    public function testHandleGetKey()
    {
        file_put_contents(CONF, $this->_minimalConfig);
        $conf = new Configuration;
        $this->assertEquals($this->_options['main']['sizelimit'], $conf->getKey('sizelimit'), 'get default size');
    }

    public function testHandleWrongTypes()
    {
        $original_options                                    = $this->_options;
        $original_options['main']['syntaxhighlightingtheme'] = 'foo';
        $options                                             = $original_options;
        $options['main']['discussion']                       = 'true';
        $options['main']['opendiscussion']                   = 0;
        $options['main']['password']                         = -1; // evaluates to TRUE
        $options['main']['fileupload']                       = 'false';
        $options['expire_options']['foo']                    = 'bar';
        $options['formatter_options'][]                      = 'foo';
        Helper::createIniFile(CONF, $options);
        $conf                                      = new Configuration;
        $original_options['expire_options']['foo'] = intval('bar');
        $original_options['formatter_options'][0]  = 'foo';
        $this->assertEquals($original_options, $conf->get(), 'incorrect types are corrected');
    }

    public function testHandleMissingSubKeys()
    {
        $options = $this->_options;
        unset($options['expire_options']['1week']);
        unset($options['expire_options']['1year']);
        unset($options['expire_options']['never']);
        $sri_key                         = array_key_first($options['sri']);
        $valid_sri                       = $options['sri'][$sri_key];
        $options['sri'][$sri_key]        = ''; // empty string should get replaced with default
        $options['sri']['js/example.js'] = 'some invalid SRI hash';
        Helper::createIniFile(CONF, $options);
        $conf                         = new Configuration;
        // restore expected results
        $options['expire']['default'] = '5min';
        $options['sri'][$sri_key]     = $valid_sri;
        $this->assertEquals($options, $conf->get(), 'not overriding "missing" subkeys');
    }

    public function testHandlePreRenameConfig()
    {
        $options                   = $this->_options;
        $options['model']['class'] = 'zerobin_data';
        Helper::createIniFile(CONF, $options);
        $conf = new Configuration;
        $this->assertEquals('Filesystem', $conf->getKey('class', 'model'), 'old data class gets renamed');

        $options['model']['class'] = 'zerobin_db';
        Helper::createIniFile(CONF, $options);
        $conf = new Configuration;
        $this->assertEquals('Database', $conf->getKey('class', 'model'), 'old db class gets renamed');
    }

    public function testConfigPath()
    {
        // setup
        $configFile              = $this->_path . DIRECTORY_SEPARATOR . 'conf.php';
        $options                 = $this->_options;
        $options['main']['name'] = 'OtherBin';
        Helper::createIniFile($configFile, $options);

        // test
        putenv('CONFIG_PATH=' . $this->_path);
        $conf = new Configuration;
        $this->assertEquals('OtherBin', $conf->getKey('name'), 'changing config path is supported');

        // cleanup environment
        if (is_file($configFile)) {
            unlink($configFile);
        }
        putenv('CONFIG_PATH');
    }
}
