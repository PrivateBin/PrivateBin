#!/usr/bin/env php
<?php
/**
 * generates a config unit test class
 *
 * This generator is meant to test all possible configuration combinations
 * without having to write endless amounts of code manually.
 *
 * DANGER: Too many options/settings and too high max iteration setting may trigger
 *         a fork bomb. Please save your work before executing this script.
 */

include 'bootstrap.php';
new configurationTestGenerator(array(
    'main/opendiscussion' => array(
        array(
            'setting' => true,
            'tests' => array(
                array(
                    'type' => 'NotTag',
                    'args' => array(
                        array(
                            'id' => 'opendiscussion',
                            'attributes' => array(
                                'disabled' => 'disabled',
                            ),
                        ),
                        '$content',
                        'outputs enabled discussion correctly'
                    ),
                ),
            ),
            'affects' => array('view')
        ), array(
            'setting' => false,
            'tests' => array(
                array(
                    'type' => 'Tag',
                    'args' => array(
                        array(
                            'id' => 'opendiscussion',
                            'attributes' => array(
                                'disabled' => 'disabled',
                            ),
                        ),
                        '$content',
                        'outputs disabled discussion correctly'
                    ),
                ),
            ),
            'affects' => array('view')
        ),
    ),
    'main/syntaxhighlighting' => array(
        array(
            'setting' => true,
            'tests' => array(
                array(
                    'type' => 'Tag',
                    'args' => array(
                        array(
                            'tag' => 'link',
                            'attributes' => array(
                                'type' => 'text/css',
                                'rel' => 'stylesheet',
                                'href' => 'regexp:#css/prettify/prettify.css#',
                            ),
                        ),
                        '$content',
                        'outputs prettify stylesheet correctly',
                    ),
                ), array(
                    'type' => 'Tag',
                    'args' => array(
                        array(
                            'tag' => 'script',
                            'attributes' => array(
                                'type' => 'text/javascript',
                                'src' => 'regexp:#js/prettify.js#'
                            ),
                        ),
                        '$content',
                        'outputs prettify javascript correctly',
                    ),
                ),
            ),
            'affects' => array('view'),
        ), array(
            'setting' => false,
            'tests' => array(
                array(
                    'type' => 'NotTag',
                    'args' => array(
                        array(
                            'tag' => 'link',
                            'attributes' => array(
                                'type' => 'text/css',
                                'rel' => 'stylesheet',
                                'href' => 'regexp:#css/prettify/prettify.css#',
                            ),
                        ),
                        '$content',
                        'removes prettify stylesheet correctly',
                    ),
                ), array(
                    'type' => 'NotTag',
                    'args' => array(
                        array(
                            'tag' => 'script',
                            'attributes' => array(
                                'type' => 'text/javascript',
                                'src' => 'regexp:#js/prettify.js#',
                            ),
                        ),
                        '$content',
                        'removes prettify javascript correctly',
                    ),
                ),
            ),
            'affects' => array('view'),
        ),
    ),
    'main/syntaxhighlightingtheme' => array(
        array(
            'setting' => 'sons-of-obsidian',
            'tests' => array(
                array(
                    'conditions' => array('main/syntaxhighlighting' => true),
                    'type' => 'Tag',
                    'args' => array(
                        array(
                            'tag' => 'link',
                            'attributes' => array(
                                'type' => 'text/css',
                                'rel' => 'stylesheet',
                                'href' => 'regexp:#css/prettify/sons-of-obsidian.css#',
                            ),
                        ),
                        '$content',
                        'outputs prettify theme stylesheet correctly',
                    ),
                ),
                array(
                    'conditions' => array('main/syntaxhighlighting' => false),
                    'type' => 'NotTag',
                    'args' => array(
                        array(
                            'tag' => 'link',
                            'attributes' => array(
                                'type' => 'text/css',
                                'rel' => 'stylesheet',
                                'href' => 'regexp:#css/prettify/sons-of-obsidian.css#',
                            ),
                        ),
                        '$content',
                        'removes prettify theme stylesheet correctly',
                    ),
                ),
            ),
            'affects' => array('view'),
        ), array(
            'setting' => null, // option not set
            'tests' => array(
                array(
                    'type' => 'NotTag',
                    'args' => array(
                        array(
                            'tag' => 'link',
                            'attributes' => array(
                                'type' => 'text/css',
                                'rel' => 'stylesheet',
                                'href' => 'regexp:#css/prettify/sons-of-obsidian.css#',
                            ),
                        ),
                        '$content',
                        'removes prettify theme stylesheet correctly',
                    ),
                ),
            ),
            'affects' => array('view'),
        ),
    ),
));

class configurationTestGenerator
{
    /**
     * endless loop protection, since we're working with a recursive function,
     * creating factorial configurations
     * @var int
     */
    const MAX_ITERATIONS = 1000;

    /**
     * options to test
     * @var array
     */
    private $_options;

    /**
     * iteration count to guarantee timely end
     * @var int
     */
    private $_iterationCount = 0;

    /**
     * generated configurations
     * @var array
     */
    private $_configurations = array(
        array('options' => array(), 'tests' => array(), 'affects' => array())
    );

    /**
     * constructor, generates the configuration test
     * @param array $options
     */
    public function __construct($options) {
        $this->_options = $options;
        // generate all possible combinations of options: options^settings
        $this->_generateConfigurations();
        $this->_writeConfigurationTest();
    }

    /**
     * write configuration test file based on generated configuration array
     */
    private function _writeConfigurationTest()
    {
        $defaultOptions = parse_ini_file(PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.ini', true);
        $code = $this->_getHeader();
        foreach ($this->_configurations as $key => $conf) {
            $fullOptions = array_replace_recursive($defaultOptions, $conf['options']);
            $options = helper::var_export_min($fullOptions, true);
            foreach ($conf['affects'] as $step) {
                $step = ucfirst($step);
                switch ($step) {
                    case 'Create':
                        $code .= <<<EOT
    /**
     * @runInSeparateProcess
     */
    public function test$step$key()
    {
        \$this->reset($options);
        \$_POST = self::\$paste;
        \$_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        \$content = ob_get_contents();

        \$response = json_decode(\$content, true);
        \$this->assertEquals(\$response['status'], 0, 'outputs status');
        \$this->assertEquals(
            \$response['deletetoken'],
            hash_hmac('sha1', \$response['id'], serversalt::get()),
            'outputs valid delete token'
        );
        \$this->assertTrue(\$this->_model->exists(\$response['id']), 'paste exists after posting data');


EOT;
                        break;
                    case 'Read':
                        $code .= <<<EOT
    /**
     * @runInSeparateProcess
     */
    public function test$step$key()
    {
        \$this->reset($options);
        \$this->_model->create(self::\$pasteid, self::\$paste);
        \$_SERVER['QUERY_STRING'] = self::\$pasteid;
        ob_start();
        new zerobin;
        \$content = ob_get_contents();

        \$this->assertTag(
            array(
                'id' => 'cipherdata',
                'content' => htmlspecialchars(json_encode(self::\$paste), ENT_NOQUOTES)
            ),
            \$content,
            'outputs data correctly'
        );


EOT;
                        break;
                    case 'Delete':
                        $code .= <<<EOT
    /**
     * @runInSeparateProcess
     */
    public function test$step$key()
    {
        \$this->reset($options);
        \$this->_model->create(self::$\pasteid, self::$\paste);
        \$this->assertTrue(\$this->_model->exists(self::$\pasteid), 'paste exists before deleting data');
        \$_GET['pasteid'] = self::$\pasteid;
        \$_GET['deletetoken'] = hash_hmac('sha1', self::$\pasteid, serversalt::get());
        ob_start();
        new zerobin;
        \$content = ob_get_contents();

        \$this->assertTag(
            array(
                'id' => 'status',
                'content' => 'Paste was properly deleted'
            ),
            \$content,
            'outputs deleted status correctly'
        );
        \$this->assertFalse(\$this->_model->exists(self::\$pasteid), 'paste successfully deleted');


EOT;
                        break;
                    // view
                    default:
                        $code .= <<<EOT
    /**
     * @runInSeparateProcess
     */
    public function test$step$key()
    {
        \$this->reset($options);
        ob_start();
        new zerobin;
        \$content = ob_get_contents();


EOT;
                }
                foreach ($conf['tests'] as $tests) {
                    foreach ($tests as $test) {
                        if (array_key_exists('conditions', $test)) {
                            while(list($path, $setting) = each($test['conditions'])) {
                                list($section, $option) = explode('/', $path);
                                if ($fullOptions[$section][$option] !== $setting) {
                                    continue 2;
                                }
                            }
                        }
                        $args = array();
                        foreach ($test['args'] as $arg) {
                            if (is_string($arg) && strpos($arg, '$') === 0) {
                                $args[] = $arg;
                            } else {
                                $args[] = helper::var_export_min($arg, true);
                            }
                        }
                        $type = $test['type'];
                        $args = implode(', ', $args);
                        $code .= "        \$this->assert$type($args);" . PHP_EOL;
                    }
                }
                $code .= '    }' . PHP_EOL . PHP_EOL;
            }
        }
        $code .= '}' . PHP_EOL;
        file_put_contents('configuration.php', $code);
    }

    /**
     * get header of configuration test file
     *
     * @return string
     */
    private function _getHeader()
    {
        return <<<'EOT'
<?php
/**
 * DO NOT EDIT: This file is automatically generated by configGenerator.php
 */
class configurationTest extends PHPUnit_Framework_TestCase
{
    private static $pasteid = '501f02e9eeb8bcec';

    private static $paste = array(
        'data' => '{"iv":"EN39/wd5Nk8HAiSG2K5AsQ","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"QKN1DBXe5PI","ct":"8hA83xDdXjD7K2qfmw5NdA"}',
        'meta' => array(
            'postdate' => 1344803344,
            'opendiscussion' => true,
        ),
    );

    private $_model;

    private $_conf;

    public function setUp()
    {
        /* Setup Routine */
        $this->_conf = PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.ini';
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');

        $this->_model = zerobin_data::getInstance(array('dir' => PATH . 'data'));
        serversalt::setPath(PATH . 'data');
        $this->reset();
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        rename($this->_conf . '.bak', $this->_conf);
}

    public function reset($configuration = array())
    {
        $_POST = array();
        $_GET = array();
        $_SERVER = array();
        if ($this->_model->exists(self::$pasteid))
            $this->_model->delete(self::$pasteid);
        helper::createIniFile($this->_conf, $configuration);
    }

EOT;
    }

    /**
     * recursive function to generate configurations based on options
     *
     * @throws Exception
     * @return array
     */
    private function _generateConfigurations()
    {
        // recursive factorial function
        if (++$this->_iterationCount > self::MAX_ITERATIONS) {
            echo 'max iterations reached, stopping', PHP_EOL;
            return $this->_configurations;
        }
        echo "generateConfigurations: iteration $this->_iterationCount", PHP_EOL;
        $continue = list($path, $settings) = each($this->_options);
        if ($continue === false) {
            return $this->_configurations;
        }
        list($section, $option) = explode('/', $path);
        for ($c = 0, $max = count($this->_configurations); $c < $max; ++$c) {
            if (!array_key_exists($section, $this->_configurations[$c]['options'])) {
                $this->_configurations[$c]['options'][$section] = array();
            }
            if (count($settings) == 0) {
                throw new Exception("Check your \$options: option $option has no settings!");
            }
            // set the first setting in the original configuration
            $setting = current($settings);
            $this->_addSetting($this->_configurations[$c], $setting, $section, $option);

            // create clones for each of the other settings
            while ($setting = next($settings)) {
                $clone = $this->_configurations[$c];
                $this->_configurations[] = $this->_addSetting($clone, $setting, $section, $option);
            }
            reset($settings);
        }
        return $this->_generateConfigurations();
    }

    /**
     * add a setting to the given configuration
     *
     * @param array $configuration
     * @param array $setting
     * @param string $section
     * @param string $option
     * @throws Exception
     * @return array
     */
    private function _addSetting(&$configuration, &$setting, &$section, &$option) {
        if (++$this->_iterationCount > self::MAX_ITERATIONS) {
            echo 'max iterations reached, stopping', PHP_EOL;
            return $configuration;
        }
        echo "addSetting: iteration $this->_iterationCount", PHP_EOL;
        if (
            array_key_exists($option, $configuration['options'][$section]) &&
            $configuration['options'][$section][$option] === $setting['setting']
        ) {
            $val = helper::var_export_min($setting['setting'], true);
            throw new Exception("Endless loop or error in options detected: option '$option' already exists with setting '$val' in one of the configurations!");
        }
        $configuration['options'][$section][$option] = $setting['setting'];
        $configuration['tests'][$option] = $setting['tests'];
        foreach ($setting['affects'] as $affects) {
            if (!in_array($affects, $configuration['affects'])) {
                $configuration['affects'][] = $affects;
            }
        }
        return $configuration;
    }
}
