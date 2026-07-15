<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin;

use Exception;
use PrivateBin\Exception\TranslatedException;

/**
 * Configuration
 *
 * parses configuration file, ensures default values present
 */
class Configuration
{
    /**
     * parsed configuration
     *
     * @var array
     */
    protected $_configuration;

    /**
     * default configuration
     *
     * @var array
     */
    private static $_defaults = array(
        'main' => array(
            'name'                     => 'PrivateBin',
            'basepath'                 => '',
            'discussion'               => true,
            'opendiscussion'           => false,
            'discussiondatedisplay'    => true,
            'password'                 => true,
            'fileupload'               => false,
            'burnafterreadingselected' => false,
            'defaultformatter'         => 'plaintext',
            'syntaxhighlightingtheme'  => '',
            'sizelimit'                => 10485760,
            'templateselection'        => false,
            'template'                 => 'bootstrap5',
            'availabletemplates'       => array(
                'bootstrap5',
                'bootstrap',
                'bootstrap-page',
                'bootstrap-dark',
                'bootstrap-dark-page',
                'bootstrap-compact',
                'bootstrap-compact-page',
            ),
            'info'                     => 'More information on the <a href=\'https://privatebin.info/\'>project page</a>.',
            'notice'                   => '',
            'languageselection'        => false,
            'languagedefault'          => '',
            'urlshortener'             => '',
            'shortenbydefault'         => false,
            'qrcode'                   => true,
            'email'                    => true,
            'icon'                     => 'jdenticon',
            'cspheader'                => 'default-src \'none\'; base-uri \'self\'; form-action \'none\'; manifest-src \'self\'; connect-src * blob:; script-src \'self\' \'wasm-unsafe-eval\'; style-src \'self\'; font-src \'self\'; frame-ancestors \'none\'; frame-src blob:; img-src \'self\' data: blob:; media-src blob:; object-src blob:; sandbox allow-same-origin allow-scripts allow-forms allow-modals allow-downloads',
            'httpwarning'              => true,
            'compression'              => 'zlib',
        ),
        'expire' => array(
            'default' => '1week',
        ),
        'expire_options' => array(
            '5min'   => 300,
            '10min'  => 600,
            '1hour'  => 3600,
            '1day'   => 86400,
            '1week'  => 604800,
            '1month' => 2592000,
            '1year'  => 31536000,
            'never'  => 0,
        ),
        'formatter_options' => array(
            'plaintext'          => 'Plain Text',
            'syntaxhighlighting' => 'Source Code',
            'markdown'           => 'Markdown',
        ),
        'traffic' => array(
            'limit'     => 10,
            'header'    => '',
            'exempted'  => '',
            'creators'  => '',
        ),
        'purge' => array(
            'limit'     => 300,
            'batchsize' => 10,
        ),
        'model' => array(
            'class' => 'Filesystem',
        ),
        'model_options' => array(
            'dir' => 'data',
        ),
        'yourls' => array(
            'signature' => '',
            'apiurl'    => '',
        ),
        'shlink' => array(
            'apikey'    => '',
            'apiurl'    => '',
        ),
        // update this array when adding/changing/removing js files
        'sri' => array(
            'js/base-x-5.0.1.js'     => 'sha512-lQWkkR3RoR+UnEUmpGDW1oU42cNFC3DqTTlzyavWPsEnRUC3AsgeDIULVZS/sgYtGcGYf9hkdfmFZxoQTlADqg==',
            'js/bootstrap-3.4.1.js'  => 'sha512-a+7UiPW8NBGU3yPMWhEz7/9ELDDg6AgR/32rG7tz6AnRyip6T9AhYDZOjOeBuqeIwPR8KRlGoysGr45kQ1502A==',
            'js/bootstrap-5.3.8.js'  => 'sha512-HJ7pgzIh9FY4uOYOVIOX+gOSIgFihDYqvn+xne/jItg/x1uRvyFK6YZEh8/8n7gJ98IIMDMmbh45Jfh3PPG+tw==',
            'js/dark-mode-switch.js' => 'sha512-upTpSQsIqd58y8LnFZouHquHBUhesxcREpIUXKecrmheT9hknQD9aMGDjvmF9RvV2InUuUG2Wiz1VY2zlEOfpg==',
            'js/flourite-1.3.0.js'   => 'sha512-50AIkNeUyvEwUGv+lSexw0A7Dh7N1EZDkcU3WNaolZ5QZglNq6DMRXCH+X0tlTWP05s3t6+NPdwOWR1yD0dGEg==',
            'js/jquery-3.7.1.js'     => 'sha512-BTUqiQhMO3R8N17qIQe5s8Zg/7WYnUjxDuMOSs+RfbIfp85W+bOF3g/P0Ic8TE6dlsSPLzjibVzV3SjteSw+Bg==',
            'js/kjua-0.10.0.js'      => 'sha512-4OWnknAe/sab+CPPsPSy1ZSSF8hpsao/pw9rdSjM1K4n4Pi1YxtbKjmgX3blp463n4L1i0DnUGqqAwQQn+BA1g==',
            'js/legacy.js'           => 'sha512-faB9AMEENIOjqqmgJVGG0Bsjnau+eSDBmmprAjw0rz2c5CZ568xsA5D2Mkxcdx1wQOmMAhbc6ufqUy5duHnhWg==',
            'js/prism-1.29.0.js'     => 'sha512-rDO6MU/BcqWAh+pcXN4ALtBd4qkipNK8Af39Z2ANJdFNetqRnVMzPOJd6NEb2464jKysy7R44+VsMkcdzhdYJA==',
            'js/privatebin.js'       => 'sha512-cbO9qOe15scEgI3RtnIx7UYxdapd4zKiIfzinRf9+1fkcQwCnhVM1NUPg06bl9EMbkx0HOFEpzymouM4B07aQg==',
            'js/purify-3.4.1.js'     => 'sha512-OLvA3X2hZx/AKH4YUVIFv3kc6tUJyUaRLIEe5y3cH7m0md+0VxUhH519vvDFxdXnXwHqOZE9uBVVoOVp4mViSA==',
            'js/showdown-2.1.0.js'   => 'sha512-dvEawkkakwSF0zTRvnCwdJ9zBS3FmVSUwdFxtW7bVf7nHxT9RIO/rdJgqj5KJyTk5mBwbakCq7zkQS74H70b7Q==',
            'js/zlib-1.3.2.js'       => 'sha512-9dsyP6TlnywTwu8MHkX8hrv80BkWtwGn2qd/sXxqkWoUv0KQ6dQBBZm7Uhebldl2xJ/5QlzlYjhee9OFgURlLA==',
            'js/zlib.js'             => 'sha512-+cYOciay49Fj3r0Pvy1OhWzEZyjdWMm0RSq5Xz6ZB2bDxnF+l/KpC6gDhzuenFjPZrAEZ91KofnOICYGppTy1w==',
        ),
    );

    /**
     * parse configuration file and ensure default configuration values are present
     *
     * @throws TranslatedException
     */
    public function __construct()
    {
        $basePaths  = array();
        $config     = array();
        $configPath = getenv('CONFIG_PATH');
        if ($configPath !== false && !empty($configPath)) {
            $basePaths[] = $configPath;
        }
        $basePaths[] = PATH . 'cfg';
        foreach ($basePaths as $basePath) {
            $configFile = $basePath . DIRECTORY_SEPARATOR . 'conf.php';
            if (is_readable($configFile)) {
                $config = parse_ini_file($configFile, true);
                foreach (array('main', 'model', 'model_options') as $section) {
                    if (!array_key_exists($section, $config)) {
                        $name = $config['main']['name'] ?? self::getDefaults()['main']['name'];
                        throw new TranslatedException(array('%s requires configuration section [%s] to be present in configuration file.', I18n::_($name), $section), 2);
                    }
                }
                break;
            }
        }

        $opts = '_options';
        foreach (self::getDefaults() as $section => $values) {
            // fill missing sections with default values
            if (!array_key_exists($section, $config) || count($config[$section]) === 0) {
                $this->_configuration[$section] = $values;
                if (array_key_exists('dir', $this->_configuration[$section])) {
                    $this->_configuration[$section]['dir'] = PATH . $this->_configuration[$section]['dir'];
                }
                continue;
            }
            // provide different defaults for database model
            elseif (
                $section === 'model_options' &&
                $this->_configuration['model']['class'] === 'Database'
            ) {
                $values = array(
                    'dsn' => 'sqlite:' . PATH . 'data' . DIRECTORY_SEPARATOR . 'db.sq3',
                    'tbl' => null,
                    'usr' => null,
                    'pwd' => null,
                    'opt' => array(),
                );
            } elseif (
                $section === 'model_options' &&
                $this->_configuration['model']['class'] === 'GoogleCloudStorage'
            ) {
                $values = array(
                    'bucket'     => getenv('PRIVATEBIN_GCS_BUCKET') ? getenv('PRIVATEBIN_GCS_BUCKET') : null,
                    'prefix'     => 'pastes',
                    'uniformacl' => false,
                );
            } elseif (
                $section === 'model_options' &&
                $this->_configuration['model']['class'] === 'S3Storage'
            ) {
                $values = array(
                    'region'                  => null,
                    'version'                 => null,
                    'endpoint'                => null,
                    'accesskey'               => null,
                    'secretkey'               => null,
                    'use_path_style_endpoint' => null,
                    'bucket'                  => null,
                    'prefix'                  => '',
                );
            }

            // "*_options" sections don't require all defaults to be set
            if (
                $section !== 'model_options' &&
                ($from = strlen($section) - strlen($opts)) >= 0 &&
                strpos($section, $opts, $from) !== false
            ) {
                if (is_int(current($values))) {
                    $config[$section] = array_map('intval', $config[$section]);
                }
                $this->_configuration[$section] = $config[$section];
            }
            // check for missing keys and set defaults if necessary
            else {
                // preserve configured SRI hashes
                if ($section === 'sri' && array_key_exists($section, $config)) {
                    $this->_configuration[$section] = $config[$section];
                }
                foreach ($values as $key => $val) {
                    if ($key === 'dir') {
                        $val = PATH . $val;
                    }
                    $result = $val;
                    if (array_key_exists($key, $config[$section])) {
                        if ($val === null) {
                            $result = $config[$section][$key];
                        } elseif (is_bool($val)) {
                            $val = strtolower($config[$section][$key]);
                            if (in_array($val, array('true', 'yes', 'on'))) {
                                $result = true;
                            } elseif (in_array($val, array('false', 'no', 'off'))) {
                                $result = false;
                            } else {
                                $result = (bool) $config[$section][$key];
                            }
                        } elseif (is_int($val)) {
                            $result = (int) $config[$section][$key];
                        } elseif (is_string($val) && !empty($config[$section][$key])) {
                            $result = (string) $config[$section][$key];
                        } elseif (is_array($val) && is_array($config[$section][$key])) {
                            $result = $config[$section][$key];
                        }
                    }
                    $this->_configuration[$section][$key] = $result;
                }
            }
        }

        // ensure a valid expire default key is set
        if (!array_key_exists($this->_configuration['expire']['default'], $this->_configuration['expire_options'])) {
            $this->_configuration['expire']['default'] = key($this->_configuration['expire_options']);
        }

        // ensure the basepath ends in a slash, if one is set
        if (
            !empty($this->_configuration['main']['basepath']) &&
            substr_compare($this->_configuration['main']['basepath'], '/', -1) !== 0
        ) {
            $this->_configuration['main']['basepath'] .= '/';
        }
    }

    /**
     * get configuration as array
     *
     * @return array
     */
    public function get()
    {
        return $this->_configuration;
    }

    /**
     * get default configuration as array
     *
     * @return array
     */
    public static function getDefaults()
    {
        return self::$_defaults;
    }

    /**
     * get a key from the configuration, typically the main section or all keys
     *
     * @param string $key
     * @param string $section defaults to main
     * @throws Exception
     * @return mixed
     */
    public function getKey($key, $section = 'main')
    {
        $options = $this->getSection($section);
        if (!array_key_exists($key, $options)) {
            throw new Exception(I18n::_('Invalid data.') . " $section / $key", 4);
        }
        return $this->_configuration[$section][$key];
    }

    /**
     * get a section from the configuration, must exist
     *
     * @param string $section
     * @throws TranslatedException
     * @return mixed
     */
    public function getSection($section)
    {
        if (!array_key_exists($section, $this->_configuration)) {
            throw new TranslatedException(array('%s requires configuration section [%s] to be present in configuration file.', I18n::_($this->getKey('name')), $section), 3);
        }
        return $this->_configuration[$section];
    }
}
