<?php

require_once __DIR__ . '/psalm-autoload.php';

/**
 * This is necessary for PHPUnit on PHP >= 5.3
 *
 * Class PHPUnit_Framework_TestCase
 */
if (PHP_VERSION_ID >= 50300) {
    if (!class_exists('PHPUnit_Framework_TestCase')) {
        require_once __DIR__ . '/other/phpunit-shim.php';
    }
}
