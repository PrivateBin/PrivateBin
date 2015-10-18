<?php
error_reporting( E_ALL | E_STRICT );

// change this, if your php files and data is outside of your webservers document root
if (!defined('PATH')) define('PATH', '..' . DIRECTORY_SEPARATOR);
if (!defined('CONF')) define('CONF', PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.ini');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', '..');

require PATH . 'lib/auto.php';

class helper
{
    /**
     * example ID of a paste
     *
     * @var string
     */
    private static $pasteid = '5e9bc25c89fb3bf9';

    /**
     * example paste
     *
     * @var array
     */
    private static $paste = array(
        'data' => '{"iv":"EN39/wd5Nk8HAiSG2K5AsQ","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"QKN1DBXe5PI","ct":"8hA83xDdXjD7K2qfmw5NdA"}',
        'attachment' => '{"iv":"Pd4pOKWkmDTT9uPwVwd5Ag","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"6nOCU3peNDclDDpFtJEBKA"}',
        'attachmentname' => '{"iv":"76MkAtOGC4oFogX/aSMxRA","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"b6Ae/U1xJdsX/+lATud4sQ"}',
        'meta' => array(
            'formatter' => 'plaintext',
            'postdate' => 1344803344,
            'opendiscussion' => true,
        ),
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
    private static $comment = array(
        'data' => '{"iv":"Pd4pOKWkmDTT9uPwVwd5Ag","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"6nOCU3peNDclDDpFtJEBKA"}',
        'meta' => array(
            'nickname' => '{"iv":"76MkAtOGC4oFogX/aSMxRA","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"b6Ae/U1xJdsX/+lATud4sQ"}',
            'vizhash' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAABGUlEQVQokWOsl5/94983CNKQMjnxaOePf98MeKwPfNjkLZ3AgARab6b9+PeNEVnDj3/ff/z7ZiHnzsDA8Pv7H2TVPJw8EAYLAwb48OaVgIgYKycLsrYv378wMDB8//qdCVMDRA9EKSsnCwRBxNsepaLboMFlyMDAICAi9uHNK24GITQ/MDAwoNhgIGMLtwGrzegaLjw5jMz9+vUdnN17uwDCQDhJgk0O07yvX9+teDX1x79v6DYIsIjgcgMaYGFgYOBg4kJx2JejkAiBxAw+PzAwMNz4dp6wDXDw4MdNNOl0rWYsNkD89OLXI/xmo9sgzatJjAYmBgYGDiauD3/ePP18nVgb4MF89+M5ZX6js293wUMpnr8KTQMAxsCJnJ30apMAAAAASUVORK5CYII=',
            'postdate' => 1344803528,
        ),
    );

    /**
     * get example paste ID
     *
     * @return string
     */
    public static function getPasteId()
    {
        return self::$pasteid;
    }

    /**
     * get example paste
     *
     * @return array
     */
    public static function getPaste($meta = array())
    {
        $example = self::getPasteWithAttachment($meta);
        unset($example['attachment'], $example['attachmentname']);
        return $example;
    }

    /**
     * get example paste
     *
     * @return array
     */
    public static function getPasteWithAttachment($meta = array())
    {
        $example = self::$paste;
        $example['meta'] = array_merge($example['meta'], $meta);
        return $example;
    }

    /**
     * get example paste
     *
     * @return array
     */
    public static function getPasteAsJson($meta = array())
    {
        $example = self::getPaste();
        if (count($meta))
            $example['meta'] = $meta;
        $example['comments'] = array();
        $example['comment_count'] = 0;
        $example['comment_offset'] = 0;
        $example['@context'] = 'js/paste.jsonld';
        return json_encode($example);
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
     * get example comment
     *
     * @return array
     */
    public static function getComment($meta = array())
    {
        $example = self::$comment;
        $example['meta'] = array_merge($example['meta'], $meta);
        return $example;
    }

    /**
     * get example comment
     *
     * @return array
     */
    public static function getCommentPost($meta = array())
    {
        $example = self::getComment($meta);
        $example['nickname'] = $example['meta']['nickname'];
        unset($example['meta']['nickname']);
        return $example;
    }

    /**
     * delete directory and all its contents recursively
     *
     * @param string $path
     * @throws Exception
     */
    public static function rmdir($path)
    {
        $path .= DIRECTORY_SEPARATOR;
        $dir = dir($path);
        while(false !== ($file = $dir->read())) {
            if($file != '.' && $file != '..') {
                if(is_dir($path . $file)) {
                    self::rmdir($path . $file);
                } elseif(is_file($path . $file)) {
                    if(!@unlink($path . $file)) {
                        throw new Exception('Error deleting file "' . $path . $file . '".');
                    }
                }
            }
        }
        $dir->close();
        if(!@rmdir($path)) {
            throw new Exception('Error deleting directory "' . $path . '".');
        }
    }

    /**
     * create a backup of the config file
     *
     * @return void
     */
    public static function confBackup()
    {
        if (!is_file(CONF . '.bak') && is_file(CONF))
            rename(CONF, CONF . '.bak');
    }

    /**
     * restor backup of the config file
     *
     * @return void
     */
    public static function confRestore()
    {
        if (is_file(CONF . '.bak'))
            rename(CONF . '.bak', CONF);
    }

    /**
     * create ini file
     *
     * @param string $pathToFile
     * @param array $values
     */
    public static function createIniFile($pathToFile, $values)
    {
        if (count($values)) {
            @unlink($pathToFile);
            $ini = fopen($pathToFile, 'a');
            foreach ($values as $section => $options) {
                fwrite($ini, "[$section]" . PHP_EOL);
                foreach($options as $option => $setting) {
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
    public static function var_export_min($var, $return = false)
    {
        if (is_array($var)) {
            $toImplode = array();
            foreach ($var as $key => $value) {
                $toImplode[] = var_export($key, true) . ' => ' . self::var_export_min($value, true);
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
}