<?php
error_reporting( E_ALL | E_STRICT );

// change this, if your php files and data is outside of your webservers document root
define('PATH', '..' . DIRECTORY_SEPARATOR);

require PATH . 'lib/auto.php';

class helper
{
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
}