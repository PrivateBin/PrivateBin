<?php

use PrivateBin\Persistence\ServerSalt;

error_reporting(E_ALL | E_STRICT);

// change this, if your php files and data is outside of your webservers document root
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', '..');
}
if (!defined('PATH')) {
    define('PATH', '..' . DIRECTORY_SEPARATOR);
}
if (!defined('CONF')) {
    define('CONF', PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php');
}
if (!defined('CONF_SAMPLE')) {
    define('CONF_SAMPLE', PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.sample.php');
}

require PATH . 'vendor/autoload.php';
Helper::updateSubresourceIntegrity();

class Helper
{
    /**
     * example ID of a paste
     *
     * @var string
     */
    private static $pasteid = '5b65a01b43987bc2';

    /**
     * example paste version 1
     *
     * @var array
     */
    private static $pasteV1 = array(
        'data'           => '{"iv":"EN39/wd5Nk8HAiSG2K5AsQ","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"QKN1DBXe5PI","ct":"8hA83xDdXjD7K2qfmw5NdA"}',
        'attachment'     => '{"iv":"Pd4pOKWkmDTT9uPwVwd5Ag","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"6nOCU3peNDclDDpFtJEBKA"}',
        'attachmentname' => '{"iv":"76MkAtOGC4oFogX/aSMxRA","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"b6Ae/U1xJdsX/+lATud4sQ"}',
        'meta'           => array(
            'formatter'      => 'plaintext',
            'postdate'       => 1344803344,
            'opendiscussion' => true,
        ),
    );

    /**
     * example paste version 2
     *
     * @var array
     */
    private static $pasteV2 = array(
        'adata' => array(
            array(
                'gMSNoLOk4z0RnmsYwXZ8mw==',
                'TZO+JWuIuxs=',
                100000,
                256,
                128,
                'aes',
                'gcm',
                'zlib',
            ),
            'plaintext',
            1,
            0,
        ),
        'meta' => array(
            'expire'  => '5min',
            'created' => 1344803344,
        ),
        'v'  => 2,
        'ct' => 'ME5JF/YBEijp2uYMzLZozbKtWc5wfy6R59NBb7SmRig=',
    );

    /**
     * example ID of a comment
     *
     * @var string
     */
    private static $commentid = '5a52eebf11c4c94b';

    /**
     * example comment
     *
     * @var array
     */
    private static $commentV1 = array(
        'data' => '{"iv":"Pd4pOKWkmDTT9uPwVwd5Ag","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"6nOCU3peNDclDDpFtJEBKA"}',
        'meta' => array(
            'nickname' => '{"iv":"76MkAtOGC4oFogX/aSMxRA","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"b6Ae/U1xJdsX/+lATud4sQ"}',
            'vizhash'  => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAABGUlEQVQokWOsl5/94983CNKQMjnxaOePf98MeKwPfNjkLZ3AgARab6b9+PeNEVnDj3/ff/z7ZiHnzsDA8Pv7H2TVPJw8EAYLAwb48OaVgIgYKycLsrYv378wMDB8//qdCVMDRA9EKSsnCwRBxNsepaLboMFlyMDAICAi9uHNK24GITQ/MDAwoNhgIGMLtwGrzegaLjw5jMz9+vUdnN17uwDCQDhJgk0O07yvX9+teDX1x79v6DYIsIjgcgMaYGFgYOBg4kJx2JejkAiBxAw+PzAwMNz4dp6wDXDw4MdNNOl0rWYsNkD89OLXI/xmo9sgzatJjAYmBgYGDiauD3/ePP18nVgb4MF89+M5ZX6js293wUMpnr8KTQMAxsCJnJ30apMAAAAASUVORK5CYII=',
            'postdate' => 1344803528,
        ),
    );

    /**
     * JS files and their SRI hashes
     *
     * @var array
     */
    private static $hashes = array();

    /**
     * get example paste ID
     *
     * @return string
     */
    public static function getPasteId()
    {
        return version_compare(PHP_VERSION, '5.6', '<') ? hash('fnv164', self::$pasteV2['ct']) : self::$pasteid;
    }

    /**
     * get example paste, as stored on server
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getPaste($version = 2, array $meta = array())
    {
        $example = self::getPasteWithAttachment($version, $meta);
        // v1 has the attachment stored in a separate property
        if ($version === 1) {
            unset($example['attachment'], $example['attachmentname']);
        }
        return $example;
    }

    /**
     * get example paste with attachment, as stored on server
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getPasteWithAttachment($version = 2, array $meta = array())
    {
        $example                 = $version === 1 ? self::$pasteV1 : self::$pasteV2;
        $example['meta']['salt'] = ServerSalt::generate();
        $example['meta']         = array_merge($example['meta'], $meta);
        return $example;
    }

    /**
     * get example paste, as decoded from POST by the request object
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getPastePost($version = 2, array $meta = array())
    {
        $example         = self::getPaste($version, $meta);
        $example['meta'] = array('expire' => $example['meta']['expire']);
        return $example;
    }

    /**
     * get example paste, as received via POST by the user
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getPasteJson($version = 2, array $meta = array())
    {
        return json_encode(self::getPastePost($version, $meta));
    }

    /**
     * get example paste ID
     *
     * @return string
     */
    public static function getCommentId()
    {
        return self::$commentid;
    }

    /**
     * get example comment, as stored on server
     *
     * @param  int $version
     * @param  array $meta
     * @return array
     */
    public static function getComment($version = 2, array $meta = array())
    {
        $example         = $version === 1 ? self::$commentV1 : self::$pasteV2;
        if ($version === 2) {
            $example['adata']           = $example['adata'][0];
            $example['pasteid']         = $example['parentid']         = self::getPasteId();
            $example['meta']['created'] = self::$commentV1['meta']['postdate'];
            $example['meta']['icon']    = self::$commentV1['meta']['vizhash'];
            unset($example['meta']['expire']);
        }
        $example['meta'] = array_merge($example['meta'], $meta);
        return $example;
    }

    /**
     * get example comment, as decoded from POST by the request object
     *
     * @param  int $version
     * @return array
     */
    public static function getCommentPost()
    {
        $example = self::getComment();
        unset($example['meta']);
        return $example;
    }

    /**
     * get example comment, as received via POST by user
     *
     * @param  int $version
     * @return array
     */
    public static function getCommentJson()
    {
        return json_encode(self::getCommentPost());
    }

    /**
     * delete directory and all its contents recursively
     *
     * @param string $path
     * @throws Exception
     */
    public static function rmDir($path)
    {
        if (is_dir($path)) {
            $path .= DIRECTORY_SEPARATOR;
            $dir = dir($path);
            while (false !== ($file = $dir->read())) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . $file)) {
                        self::rmDir($path . $file);
                    } elseif (is_file($path . $file)) {
                        if (!unlink($path . $file)) {
                            throw new Exception('Error deleting file "' . $path . $file . '".');
                        }
                    }
                }
            }
            $dir->close();
            if (!rmdir($path)) {
                throw new Exception('Error deleting directory "' . $path . '".');
            }
        }
    }

    /**
     * create a backup of the config file
     *
     * @return void
     */
    public static function confBackup()
    {
        if (!is_file(CONF . '.bak') && is_file(CONF)) {
            rename(CONF, CONF . '.bak');
        }
        if (!is_file(CONF_SAMPLE . '.bak') && is_file(CONF_SAMPLE)) {
            copy(CONF_SAMPLE, CONF_SAMPLE . '.bak');
        }
    }

    /**
     * restor backup of the config file
     *
     * @return void
     */
    public static function confRestore()
    {
        if (is_file(CONF . '.bak')) {
            rename(CONF . '.bak', CONF);
        }
        if (is_file(CONF_SAMPLE . '.bak')) {
            rename(CONF_SAMPLE . '.bak', CONF_SAMPLE);
        }
    }

    /**
     * create ini file
     *
     * @param string $pathToFile
     * @param array $values
     */
    public static function createIniFile($pathToFile, array $values)
    {
        if (count($values)) {
            @unlink($pathToFile);
            $ini = fopen($pathToFile, 'a');
            foreach ($values as $section => $options) {
                fwrite($ini, "[$section]" . PHP_EOL);
                foreach ($options as $option => $setting) {
                    if (is_null($setting)) {
                        continue;
                    } elseif (is_string($setting)) {
                        $setting = '"' . $setting . '"';
                    } elseif (is_array($setting)) {
                        foreach ($setting as $key => $value) {
                            if (is_null($value)) {
                                $value = 'null';
                            } elseif (is_string($value)) {
                                $value = '"' . $value . '"';
                            } else {
                                $value = var_export($value, true);
                            }
                            fwrite($ini, $option . "[$key] = $value" . PHP_EOL);
                        }
                        continue;
                    } else {
                        $setting = var_export($setting, true);
                    }
                    fwrite($ini, "$option = $setting" . PHP_EOL);
                }
                fwrite($ini, PHP_EOL);
            }
            fclose($ini);
        }
    }

    /**
     * a var_export that returns arrays without line breaks
     * by linus@flowingcreativity.net via php.net
     *
     * @param mixed $var
     * @param bool $return
     * @return void|string
     */
    public static function varExportMin($var, $return = false)
    {
        if (is_array($var)) {
            $toImplode = array();
            foreach ($var as $key => $value) {
                $toImplode[] = var_export($key, true) . ' => ' . self::varExportMin($value, true);
            }
            $code = 'array(' . implode(', ', $toImplode) . ')';
            if ($return) {
                return $code;
            } else {
                echo $code;
            }
        } else {
            return var_export($var, $return);
        }
    }

    /**
     * update all templates with the latest SRI hashes for all JS files
     *
     * @return void
     */
    public static function updateSubresourceIntegrity()
    {
        $dir = dir(PATH . 'js');
        while (false !== ($file = $dir->read())) {
            if (substr($file, -3) === '.js') {
                self::$hashes[$file] = base64_encode(
                    hash('sha512', file_get_contents(
                        PATH . 'js' . DIRECTORY_SEPARATOR . $file
                    ), true)
                );
            }
        }

        $dir = dir(PATH . 'tpl');
        while (false !== ($file = $dir->read())) {
            if (substr($file, -4) === '.php') {
                $content = file_get_contents(
                    PATH . 'tpl' . DIRECTORY_SEPARATOR . $file
                );
                $content = preg_replace_callback(
                    '#<script ([^>]+) src="js/([a-z0-9.-]+.js)([^"]*)"( integrity="[^"]+" crossorigin="[^"]+")?></script>#',
                    function ($matches) {
                        if (array_key_exists($matches[2], Helper::$hashes)) {
                            return '<script ' . $matches[1] . ' src="js/' .
                                $matches[2] . $matches[3] .
                                '" integrity="sha512-' . Helper::$hashes[$matches[2]] .
                                '" crossorigin="anonymous"></script>';
                        } else {
                            return $matches[0];
                        }
                    },
                    $content
                );
                file_put_contents(
                    PATH . 'tpl' . DIRECTORY_SEPARATOR . $file,
                    $content
                );
            }
        }
    }
}
