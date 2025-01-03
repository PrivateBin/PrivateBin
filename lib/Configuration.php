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
    private $_configuration;

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
            'template'                 => 'bootstrap',
            'info'                     => 'More information on the <a href=\'https://privatebin.info/\'>project page</a>.',
            'notice'                   => '',
            'languageselection'        => false,
            'languagedefault'          => '',
            'urlshortener'             => '',
            'qrcode'                   => true,
            'email'                    => true,
            'icon'                     => 'identicon',
            'cspheader'                => 'default-src \'none\'; base-uri \'self\'; form-action \'none\'; manifest-src \'self\'; connect-src * blob:; script-src \'self\' \'wasm-unsafe-eval\'; style-src \'self\'; font-src \'self\'; frame-ancestors \'none\'; img-src \'self\' data: blob:; media-src blob:; object-src blob:; sandbox allow-same-origin allow-scripts allow-forms allow-popups allow-modals allow-downloads',
            'zerobincompatibility'     => false,
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
        // update this array when adding/changing/removing js files
        'sri' => array(
            'js/base-x-4.0.0.js'     => 'sha512-nNPg5IGCwwrveZ8cA/yMGr5HiRS5Ps2H+s0J/mKTPjCPWUgFGGw7M5nqdnPD3VsRwCVysUh3Y8OWjeSKGkEQJQ==',
            'js/base64-1.7.js'       => 'sha512-JdwsSP3GyHR+jaCkns9CL9NTt4JUJqm/BsODGmYhBcj5EAPKcHYh+OiMfyHbcDLECe17TL0hjXADFkusAqiYgA==',
            'js/bootstrap-3.4.1.js'  => 'sha512-oBTprMeNEKCnqfuqKd6sbvFzmFQtlXS3e0C/RGFV0hD6QzhHV+ODfaQbAlmY6/q0ubbwlAM/nCJjkrgA3waLzg==',
            'js/bootstrap-5.3.3.js'  => 'sha512-in2rcOpLTdJ7/pw5qjF4LWHFRtgoBDxXCy49H4YGOcVdGiPaQucGIbOqxt1JvmpvOpq3J/C7VTa0FlioakB2gQ==',
            'js/dark-mode-switch.js' => 'sha512-CCbdHdeWDbDO7aqFFmhgnvFESzaILHbUYmbhNjTpcjyO/XYdouQ9Pw8W9rpV8oJT1TsK5FbwSHU1oazmnb7BWA==',
            'js/jquery-3.7.1.js'     => 'sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==',
            'js/kjua-0.9.0.js'       => 'sha512-CVn7af+vTMBd9RjoS4QM5fpLFEOtBCoB0zPtaqIDC7sF4F8qgUSRFQQpIyEDGsr6yrjbuOLzdf20tkHHmpaqwQ==',
            'js/legacy.js'           => 'sha512-UxW/TOZKon83n6dk/09GsYKIyeO5LeBHokxyIq+r7KFS5KMBeIB/EM7NrkVYIezwZBaovnyNtY2d9tKFicRlXg==',
            'js/prettify.js'         => 'sha512-puO0Ogy++IoA2Pb9IjSxV1n4+kQkKXYAEUtVzfZpQepyDPyXk8hokiYDS7ybMogYlyyEIwMLpZqVhCkARQWLMg==',
            'js/privatebin.js'       => 'sha512-cCt3Slm10JXtPJhgmYdf1RKO7uWdz6U+k0bdPjTYfdO6WeWfruN9RkJE7tKmpgw8A35H4Xifmrr2KmyVOMbc3g==',
            'js/purify-3.1.7.js'     => 'sha512-LegvqULiMtOfboJZw9MpETN/b+xnLRXZI90gG7oIFHW+yAeHmKvRtEUbiMFx2WvUqQoL9XB3gwU+hWXUT0X+8A==',
            'js/rawinflate-0.3.js'   => 'sha512-g8uelGgJW9A/Z1tB6Izxab++oj5kdD7B4qC7DHwZkB6DGMXKyzx7v5mvap2HXueI2IIn08YlRYM56jwWdm2ucQ==',
            'js/showdown-2.1.0.js'   => 'sha512-WYXZgkTR0u/Y9SVIA4nTTOih0kXMEd8RRV6MLFdL6YU8ymhR528NLlYQt1nlJQbYz4EW+ZsS0fx1awhiQJme1Q==',
            'js/zlib-1.3.1.js'       => 'sha512-5bU9IIP4PgBrOKLZvGWJD4kgfQrkTz8Z3Iqeu058mbQzW3mCumOU6M3UVbVZU9rrVoVwaW4cZK8U8h5xjF88eQ==',
        ),
    );

    /**
     * parse configuration file and ensure default configuration values are present
     *
     * @throws Exception
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
                        throw new Exception(I18n::_('PrivateBin requires configuration section [%s] to be present in configuration file.', $section), 2);
                    }
                }
                break;
            }
        }

        $opts = '_options';
        foreach (self::getDefaults() as $section => $values) {
            // fill missing sections with default values
            if (!array_key_exists($section, $config) || count($config[$section]) == 0) {
                $this->_configuration[$section] = $values;
                if (array_key_exists('dir', $this->_configuration[$section])) {
                    $this->_configuration[$section]['dir'] = PATH . $this->_configuration[$section]['dir'];
                }
                continue;
            }
            // provide different defaults for database model
            elseif (
                $section == 'model_options' && in_array(
                    $this->_configuration['model']['class'],
                    array('Database', 'privatebin_db', 'zerobin_db')
                )
            ) {
                $values = array(
                    'dsn' => 'sqlite:' . PATH . 'data' . DIRECTORY_SEPARATOR . 'db.sq3',
                    'tbl' => null,
                    'usr' => null,
                    'pwd' => null,
                    'opt' => array(),
                );
            } elseif (
                $section == 'model_options' && in_array(
                    $this->_configuration['model']['class'],
                    array('GoogleCloudStorage')
                )
            ) {
                $values = array(
                    'bucket'     => getenv('PRIVATEBIN_GCS_BUCKET') ? getenv('PRIVATEBIN_GCS_BUCKET') : null,
                    'prefix'     => 'pastes',
                    'uniformacl' => false,
                );
            } elseif (
                $section == 'model_options' && in_array(
                    $this->_configuration['model']['class'],
                    array('S3Storage')
                )
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
                if ($section == 'sri' && array_key_exists($section, $config)) {
                    $this->_configuration[$section] = $config[$section];
                }
                foreach ($values as $key => $val) {
                    if ($key == 'dir') {
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

        // support for old config file format, before the fork was renamed and PSR-4 introduced
        $this->_configuration['model']['class'] = str_replace(
            'zerobin_', 'privatebin_',
            $this->_configuration['model']['class']
        );

        $this->_configuration['model']['class'] = str_replace(
            array('privatebin_data', 'privatebin_db'),
            array('Filesystem', 'Database'),
            $this->_configuration['model']['class']
        );

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
     * @throws Exception
     * @return mixed
     */
    public function getSection($section)
    {
        if (!array_key_exists($section, $this->_configuration)) {
            throw new Exception(I18n::_('%s requires configuration section [%s] to be present in configuration file.', I18n::_($this->getKey('name')), $section), 3);
        }
        return $this->_configuration[$section];
    }
}
