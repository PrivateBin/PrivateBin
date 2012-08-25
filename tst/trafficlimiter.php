<?php
class trafficlimiterTest extends PHPUnit_Framework_TestCase
{
    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trafficlimit' . DIRECTORY_SEPARATOR;
        trafficlimiter::setPath($this->_path);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        helper::rmdir($this->_path);
    }

    public function testTrafficGetsLimited()
    {
        trafficlimiter::setLimit(4);
        $this->assertTrue(trafficlimiter::canPass('127.0.0.1'), 'first request may pass');
        sleep(2);
        $this->assertFalse(trafficlimiter::canPass('127.0.0.1'), 'second request is to fast, may not pass');
        sleep(3);
        $this->assertTrue(trafficlimiter::canPass('127.0.0.1'), 'third request waited long enough and may pass');
        $this->assertTrue(trafficlimiter::canPass('2001:1620:2057:dead:beef::cafe:babe'), 'fourth request has different ip and may pass');
        $this->assertFalse(trafficlimiter::canPass('127.0.0.1'), 'fifth request is to fast, may not pass');
    }
}
