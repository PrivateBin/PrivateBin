<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.21.1
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
        ),
        'expire' => array(
            'default' => '1week',
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
        $config = parse_ini_file(PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.ini', true);
        foreach (array('main', 'model') as $section) {
            if (!array_key_exists($section, $config)) {
                throw new Exception(i18n::_('ZeroBin requires configuration section [%s] to be present in configuration file.', $section), 2);
            }
        }
        foreach ($this->_defaults as $section => $values)
        {
            if (!array_key_exists($section, $config))
            {
                $this->_configuration[$section] = $this->_defaults[$section];
                if (array_key_exists('dir', $this->_configuration[$section]))
                {
                    $this->_configuration[$section]['dir'] = PATH . $this->_configuration[$section]['dir'];
                }
                continue;
            }
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
