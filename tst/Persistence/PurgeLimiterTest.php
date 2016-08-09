<?php

use PrivateBin\Persistence\PurgeLimiter;

class PurgeLimiterTest extends PHPUnit_Framework_TestCase
{
    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        PurgeLimiter::setPath($this->_path);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        Helper::rmDir($this->_path);
    }

    public function testLimit()
    {
        // initialize it
        PurgeLimiter::canPurge();

        // try setting it
        PurgeLimiter::setLimit(1);
        $this->assertEquals(false, PurgeLimiter::canPurge());
        sleep(2);
        $this->assertEquals(true, PurgeLimiter::canPurge());

        // disable it
        PurgeLimiter::setLimit(0);
        PurgeLimiter::canPurge();
        $this->assertEquals(true, PurgeLimiter::canPurge());
    }
}
