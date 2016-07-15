<?php
class purgelimiterTest extends PHPUnit_Framework_TestCase
{
    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if(!is_dir($this->_path)) mkdir($this->_path);
        purgelimiter::setPath($this->_path);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        helper::rmdir($this->_path);
    }

    public function testLimit()
    {
        // initialize it
        purgelimiter::canPurge();

        // try setting it
        purgelimiter::setLimit(1);
        $this->assertEquals(false, purgelimiter::canPurge());
        sleep(2);
        $this->assertEquals(true, purgelimiter::canPurge());

        // disable it
        purgelimiter::setLimit(0);
        purgelimiter::canPurge();
        $this->assertEquals(true, purgelimiter::canPurge());
    }
}
