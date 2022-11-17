<?php

use PHPUnit\Framework\TestCase;
use PrivateBin\Data\Filesystem;
use PrivateBin\Persistence\PurgeLimiter;

class PurgeLimiterTest extends TestCase
{
    private $_path;

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        PurgeLimiter::setStore(
            new Filesystem(array('dir' => $this->_path))
        );
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        Helper::rmDir($this->_path);
    }

    public function testLimit()
    {
        // initialize it
        PurgeLimiter::setLimit(1);
        PurgeLimiter::canPurge();

        // try setting it
        $this->assertEquals(false, PurgeLimiter::canPurge());
        sleep(2);
        $this->assertEquals(true, PurgeLimiter::canPurge());

        // disable it
        PurgeLimiter::setLimit(0);
        PurgeLimiter::canPurge();
        $this->assertEquals(true, PurgeLimiter::canPurge());
    }
}
