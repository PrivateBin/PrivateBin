<?php
error_reporting( E_ALL | E_STRICT );

// change this, if your php files and data is outside of your webservers document root
if (!defined('PATH')) define('PATH', '..' . DIRECTORY_SEPARATOR);

require PATH . 'lib/auto.php';

class helper
{
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