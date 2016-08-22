<?php

use PrivateBin\Configuration;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    private $_options;

    private $_minimalConfig;

    public function setUp()
    {
        /* Setup Routine */
        Helper::confBackup();
        $this->_options = configuration::getDefaults();
        $this->_options['model_options']['dir'] = PATH . $this->_options['model_options']['dir'];
        $this->_options['traffic']['dir'] = PATH . $this->_options['traffic']['dir'];
        $this->_options['purge']['dir'] = PATH . $this->_options['purge']['dir'];
        $this->_minimalConfig = '[main]' . PHP_EOL . '[model]' . PHP_EOL . '[model_options]';
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        Helper::confRestore();
    }

    public function testDefaultConfigFile()
    {
        $this->assertTrue(copy(CONF . '.bak', CONF), 'copy default configuration file');
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
        @unlink(CONF);
        $conf = new Configuration;
        $this->assertEquals($this->_options, $conf->get(), 'returns correct defaults on missing file');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 2
     */
    public function testHandleBlankConfigFile()
    {
        file_put_contents(CONF, '');
        new Configuration;
    }

    public function testHandleMinimalConfigFile()
    {
        file_put_contents(CONF, $this->_minimalConfig);
        $conf = new Configuration;
        $this->assertEquals($this->_options, $conf->get(), 'returns correct defaults on empty file');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 3
     */
    public function testHandleInvalidSection()
    {
        file_put_contents(CONF, $this->_minimalConfig);
        $conf = new Configuration;
        $conf->getKey('foo', 'bar');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 4
     */
    public function testHandleInvalidKey()
    {
        file_put_contents(CONF, $this->_minimalConfig);
        $conf = new Configuration;
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
        $original_options = $this->_options;
        $original_options['main']['syntaxhighlightingtheme'] = 'foo';
        $options = $original_options;
        $options['main']['discussion'] = 'true';
        $options['main']['opendiscussion'] = 0;
        $options['main']['password'] = -1; // evaluates to TRUE
        $options['main']['fileupload'] = 'false';
        $options['expire_options']['foo'] = 'bar';
        $options['formatter_options'][] = 'foo';
        Helper::createIniFile(CONF, $options);
        $conf = new Configuration;
        $original_options['expire_options']['foo'] = intval('bar');
        $original_options['formatter_options'][0] = 'foo';
        $this->assertEquals($original_options, $conf->get(), 'incorrect types are corrected');
    }

    public function testHandleMissingSubKeys()
    {
        $options = $this->_options;
        unset($options['expire_options']['1week']);
        unset($options['expire_options']['1year']);
        unset($options['expire_options']['never']);
        Helper::createIniFile(CONF, $options);
        $conf = new Configuration;
        $options['expire']['default'] = '5min';
        $this->assertEquals($options, $conf->get(), 'not overriding "missing" subkeys');
    }

    public function testHandlePreRenameConfig()
    {
        $options = $this->_options;
        $options['model']['class'] = 'zerobin_data';
        Helper::createIniFile(CONF, $options);
        $conf = new Configuration;
        $this->assertEquals('Filesystem', $conf->getKey('class', 'model'), 'old data class gets renamed');

        $options['model']['class'] = 'zerobin_db';
        Helper::createIniFile(CONF, $options);
        $conf = new Configuration;
        $this->assertEquals('Database', $conf->getKey('class', 'model'), 'old db class gets renamed');
    }
}
