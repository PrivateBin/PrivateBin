<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.22
 */

/**
 * configuration
 *
 * parses configuration file, ensures default values present
 */
class configuration
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
    private $_defaults = array(
        'main' => array(
            'discussion' => true,
            'opendiscussion' => false,
            'password' => true,
            'fileupload' => false,
            'burnafterreadingselected' => false,
            'defaultformatter' => 'plaintext',
            'syntaxhighlightingtheme' => null,
            'sizelimit' => 2097152,
            'template' => 'bootstrap',
            'notice' => '',
            'base64version' => '2.1.9',
            'languageselection' => false,
            'languagedefault' => '',
        ),
        'expire' => array(
            'default' => '1week',
            'clone' => true,
        ),
        'expire_options' => array(
            '5min' => 300,
            '10min' => 600,
            '1hour' => 3600,
            '1day' => 86400,
            '1week' => 604800,
            '1month' => 2592000,
            '1year' => 31536000,
            'never' => 0,
        ),
        'formatter_options' => array(
            'plaintext' => 'Plain Text',
            'syntaxhighlighting' => 'Source Code',
            'markdown' => 'Markdown',
        ),
        'traffic' => array(
            'limit' => 10,
            'header' => null,
            'dir' => 'data',
        ),
        'model' => array(
            'class' => 'zerobin_data',
        ),
        'model_options' => array(
            'dir' => 'data',
        ),
    );

    /**
     * parse configuration file and ensure default configuration values are present
     *
     * @throws Exception
     */
    public function __construct()
    {
        $config = array();
        $configFile = PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.ini';
        if (is_readable($configFile))
        {
            $config = parse_ini_file($configFile, true);
            foreach (array('main', 'model') as $section) {
                if (!array_key_exists($section, $config)) {
                    throw new Exception(i18n::_('ZeroBin requires configuration section [%s] to be present in configuration file.', $section), 2);
                }
            }
        }
        $opts = '_options';
        foreach ($this->_defaults as $section => $values)
        {
            // fill missing sections with default values
            if (!array_key_exists($section, $config) || count($config[$section]) == 0)
            {
                $this->_configuration[$section] = $values;
                if (array_key_exists('dir', $this->_configuration[$section]))
                {
                    $this->_configuration[$section]['dir'] = PATH . $this->_configuration[$section]['dir'];
                }
                continue;
            }
            // provide different defaults for database model
            elseif ($section == 'model_options' && $this->_configuration['model']['class'] == 'zerobin_db')
            {
                $values = array(
                    'dsn' => 'sqlite:' . PATH . 'data/db.sq3',
                    'tbl' => null,
                    'usr' => null,
                    'pwd' => null,
                    'opt' => array(PDO::ATTR_PERSISTENT => true),
                );
            }

            // "*_options" sections don't require all defaults to be set
            if (
                $section !== 'model_options' &&
                ($from = strlen($section) - strlen($opts)) >= 0 &&
                strpos($section, $opts, $from) !== false
            )
            {
                if (is_int(current($values)))
                {
                    $config[$section] = array_map('intval', $config[$section]);
                }
                $this->_configuration[$section] = $config[$section];
            }
            // check for missing keys and set defaults if necessary
            else
            {
                foreach ($values as $key => $val)
                {
                    if ($key == 'dir')
                    {
                        $val = PATH . $val;
                    }
                    $result = $val;
                    if (array_key_exists($key, $config[$section]))
                    {
                        if ($val === null)
                        {
                            $result = $config[$section][$key];
                        }
                        elseif (is_bool($val))
                        {
                            $val = strtolower($config[$section][$key]);
                            if (in_array($val, array('true', 'yes', 'on')))
                            {
                                $result = true;
                            }
                            elseif (in_array($val, array('false', 'no', 'off')))
                            {
                                $result = false;
                            }
                            else
                            {
                                $result = (bool) $config[$section][$key];
                            }
                        }
                        elseif (is_int($val))
                        {
                            $result = (int) $config[$section][$key];
                        }
                        elseif (is_string($val) && !empty($config[$section][$key]))
                        {
                            $result = (string) $config[$section][$key];
                        }
                    }
                    $this->_configuration[$section][$key] = $result;
                }
            }
        }

        // ensure a valid expire default key is set
        if (!array_key_exists($this->_configuration['expire']['default'], $this->_configuration['expire_options']))
        {
            $this->_configuration['expire']['default'] = key($this->_configuration['expire_options']);
        }
    }

    /**
     * get configuration as array
     *
     * return array
     */
    public function get()
    {
        return $this->_configuration;
    }


    /**
     * get a key from the configuration, typically the main section or all keys
     *
     * @param string $key
     * @param string $section defaults to main
     * @throws Exception
     * return mixed
     */
    public function getKey($key, $section = 'main')
    {
        $options = $this->getSection($section);
        if (!array_key_exists($key, $options))
        {
            throw new Exception(i18n::_('Invalid data.') . " $section / $key", 4);
        }
        return $this->_configuration[$section][$key];
    }


    /**
     * get a key from the configuration, typically the main section or all keys
     *
     * @param string $key if empty, return all configuration options
     * @param string $section defaults to main
     * @throws Exception
     * return mixed
     */
    public function getSection($section)
    {
        if (!array_key_exists($section, $this->_configuration))
        {
            throw new Exception(i18n::_('ZeroBin requires configuration section [%s] to be present in configuration file.', $section), 3);
        }
        return $this->_configuration[$section];
    }
}
