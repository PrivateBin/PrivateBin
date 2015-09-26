<?php
class trafficlimiterTest extends PHPUnit_Framework_TestCase
{
    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trafficlimit';
        trafficlimiter::setPath($this->_path);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        helper::rmdir($this->_path . DIRECTORY_SEPARATOR);
    }

    public function testTrafficGetsLimited()
    {
        $this->assertEquals($this->_path, trafficlimiter::getPath());
        $file = 'baz';
        $this->assertEquals($this->_path . DIRECTORY_SEPARATOR . $file, trafficlimiter::getPath($file));
        trafficlimiter::setLimit(4);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertTrue(trafficlimiter::canPass(), 'first request may pass');
        sleep(2);
        $this->assertFalse(trafficlimiter::canPass(), 'second request is to fast, may not pass');
        sleep(3);
        $this->assertTrue(trafficlimiter::canPass(), 'third request waited long enough and may pass');
        $_SERVER['REMOTE_ADDR'] = '2001:1620:2057:dead:beef::cafe:babe';
        $this->assertTrue(trafficlimiter::canPass(), 'fourth request has different ip and may pass');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertFalse(trafficlimiter::canPass(), 'fifth request is to fast, may not pass');
    }
}
