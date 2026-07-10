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
            'urlshortenerwarning'      => true,
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
        'auth' => array(
            'enabled'                 => false,
            'require_login_to_create' => true,
            'require_login_to_read'   => false,
            'allow_registration'      => false,
            'session_timeout'         => 3600,
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
            'js/base-x-5.0.1.js'     => 'sha512-FmhlnjIxQyxkkxQmzf0l6IRGsGbgyCdgqPxypFsEtHMF1naRqaLLo6mcyN5rEaT16nKx1PeJ4g7+07D6gnk/Tg==',
            'js/bootstrap-3.4.1.js'  => 'sha512-oBTprMeNEKCnqfuqKd6sbvFzmFQtlXS3e0C/RGFV0hD6QzhHV+ODfaQbAlmY6/q0ubbwlAM/nCJjkrgA3waLzg==',
            'js/bootstrap-5.3.8.js'  => 'sha512-BkZvJ5rZ3zbDCod5seWHpRGg+PRd6ZgE8Nua/OMtcxqm8Wtg0PqwhUUXK5bqvl3oclMt5O+3zjRVX0L+L2j7fA==',
            'js/dark-mode-switch.js' => 'sha512-BhY7dNU14aDN5L+muoUmA66x0CkYUWkQT0nxhKBLP/o2d7jE025+dvWJa4OiYffBGEFgmhrD/Sp+QMkxGMTz2g==',
            'js/jquery-3.7.1.js'     => 'sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==',
            'js/kjua-0.10.0.js'      => 'sha512-BYj4xggowR7QD150VLSTRlzH62YPfhpIM+b/1EUEr7RQpdWAGKulxWnOvjFx1FUlba4m6ihpNYuQab51H6XlYg==',
            'js/legacy.js'           => 'sha512-RQEo1hxpNc37i+jz/D9/JiAZhG8GFx3+SNxjYnI7jUgirDIqrCSj6QPAAZeaidditcWzsJ3jxfEj5lVm7ZwTRQ==',
            'js/prettify.js'         => 'sha512-puO0Ogy++IoA2Pb9IjSxV1n4+kQkKXYAEUtVzfZpQepyDPyXk8hokiYDS7ybMogYlyyEIwMLpZqVhCkARQWLMg==',
            'js/privatebin.js'       => 'sha512-zbka2ePFe4nT6QVb6tManMPWaZleeJk57R0eRgQTZNpRdu5LklTuSdHnIfFKtY7ZLbN2tER1vKYDx1eHwvnDfg==',
            'js/auth.js'             => 'sha512-plXcIZrl3H8AdHquS6BIxuXwSb6by4hk2cb1LgbRZ7b7gmwrT0yNGvsLsYmmDPtXXA32cFaSkkJNbgd1RaZjPA==',
            'js/purify-3.4.1.js'     => 'sha512-280a/Vb6fVFsYaeRrkuDp4EDmdYlt2XS+dlDEO/U9qljPrAraA2bIzHTNmP+9dpwPDDwTML+RS+h5iaagPwTzA==',
            'js/showdown-2.1.0.js'   => 'sha512-WYXZgkTR0u/Y9SVIA4nTTOih0kXMEd8RRV6MLFdL6YU8ymhR528NLlYQt1nlJQbYz4EW+ZsS0fx1awhiQJme1Q==',
            'js/zlib-1.3.2.js'       => 'sha512-RAhJgxg9siMIA8ky4c10Rc2zUgnK80olHB8Tt1IOYWY4Eh1WmrviQkDn+sgBlb38ZHq3tzufGC41kP360gmosQ==',
            'js/zlib.js'             => 'sha512-QOaEwssHqHRRcWJ2Un3Kl2Zhyprzl7T8zmsKN2FppFxW3VR+8UChYOx2iuL0HbXK42fuBWJm5PNQJxufulrt/w==',
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

    /**
     * get the configuration file path
     *
     * @return string
     */
    public static function getConfigPath(): string
    {
        $configPath = getenv('CONFIG_PATH');
        if ($configPath !== false && !empty($configPath)) {
            return $configPath . DIRECTORY_SEPARATOR . 'conf.php';
        }
        return PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php';
    }

    /**
     * get safe configuration for display (strips sensitive fields)
     *
     * @return array
     */
    public function getSafeConfig(): array
    {
        $config = $this->_configuration;
        // strip sensitive model_options (passwords, keys)
        if (isset($config['model_options']['pwd'])) {
            $config['model_options']['pwd'] = '***';
        }
        if (isset($config['model_options']['secretkey'])) {
            $config['model_options']['secretkey'] = '***';
        }
        if (isset($config['model_options']['accesskey'])) {
            $config['model_options']['accesskey'] = '***';
        }
        // strip SRI hashes (internal)
        unset($config['sri']);
        return $config;
    }

    /**
     * update configuration values and write to conf.php
     *
     * Only updates keys that exist in the current configuration.
     * Does not allow changing model/model_options for safety.
     *
     * @param array $updates keyed by section => array of key => value
     * @return bool
     */
    public function updateAndSave(array $updates): bool
    {
        // sections that cannot be changed via the admin UI
        $protectedSections = array('model', 'model_options', 'sri');

        foreach ($updates as $section => $values) {
            if (in_array($section, $protectedSections, true)) {
                continue;
            }
            if (!is_array($values)) {
                continue;
            }
            if (!array_key_exists($section, $this->_configuration)) {
                continue;
            }
            foreach ($values as $key => $value) {
                if (array_key_exists($key, $this->_configuration[$section])) {
                    $this->_configuration[$section][$key] = $value;
                }
            }
        }

        return $this->_writeConfig();
    }

    /**
     * write the current configuration to conf.php
     *
     * @access private
     * @return bool
     */
    private function _writeConfig(): bool
    {
        $configFile = self::getConfigPath();

        $conf = ";<?php http_response_code(403); /*\n";
        $conf .= "; config file for PrivateBin\n";
        $conf .= "; last updated: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($this->_configuration as $section => $values) {
            // skip SRI section (auto-managed)
            if ($section === 'sri') {
                continue;
            }

            $conf .= "[$section]\n";

            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $conf .= $key . '[] = ' . self::_formatIniValue($item) . "\n";
                    }
                } elseif (is_bool($value)) {
                    $conf .= "$key = " . ($value ? 'true' : 'false') . "\n";
                } elseif (is_int($value) || is_float($value)) {
                    $conf .= "$key = $value\n";
                } else {
                    $conf .= "$key = " . self::_formatIniValue($value) . "\n";
                }
            }
            $conf .= "\n";
        }

        return file_put_contents($configFile, $conf) !== false;
    }

    /**
     * format a value for INI file output
     *
     * @access private
     * @static
     * @param mixed $value
     * @return string
     */
    private static function _formatIniValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_null($value)) {
            return 'null';
        }
        // quote strings
        return '"' . addcslashes((string) $value, '"') . '"';
    }
}
