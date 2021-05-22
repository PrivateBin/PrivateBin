<?php

use PrivateBin\Persistence\TrafficLimiter;

class TrafficLimiterTest extends PHPUnit_Framework_TestCase
{
    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trafficlimit';
        TrafficLimiter::setPath($this->_path);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        Helper::rmDir($this->_path . DIRECTORY_SEPARATOR);
    }

    public function testTrafficGetsLimited()
    {
        $this->assertEquals($this->_path, TrafficLimiter::getPath());
        $file = 'baz';
        $this->assertEquals($this->_path . DIRECTORY_SEPARATOR . $file, TrafficLimiter::getPath($file));
        TrafficLimiter::setLimit(4);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertTrue(TrafficLimiter::canPass(), 'first request may pass');
        sleep(1);
        $this->assertFalse(TrafficLimiter::canPass(), 'second request is to fast, may not pass');
        sleep(4);
        $this->assertTrue(TrafficLimiter::canPass(), 'third request waited long enough and may pass');
        $_SERVER['REMOTE_ADDR'] = '2001:1620:2057:dead:beef::cafe:babe';
        $this->assertTrue(TrafficLimiter::canPass(), 'fourth request has different ip and may pass');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertFalse(TrafficLimiter::canPass(), 'fifth request is to fast, may not pass');

        // exempted IPs configuration
        TrafficLimiter::setExemptedIp('1.2.3.4,10.10.10.0/24,2001:1620:2057::/48');
        $this->assertFalse(TrafficLimiter::canPass(), 'still too fast and not exempted');
        $_SERVER['REMOTE_ADDR'] = '10.10.10.10';
        $this->assertTrue(TrafficLimiter::canPass(), 'IPv4 in exempted range');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is to fast, but IPv4 in exempted range');
        $_SERVER['REMOTE_ADDR'] = '2001:1620:2057:dead:beef::cafe:babe';
        $this->assertTrue(TrafficLimiter::canPass(), 'IPv6 in exempted range');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is to fast, but IPv6 in exempted range');
        TrafficLimiter::setExemptedIp('127.*,foobar');
        $this->assertFalse(TrafficLimiter::canPass(), 'request is to fast, invalid range');
        $_SERVER['REMOTE_ADDR'] = 'foobar';
        $this->assertTrue(TrafficLimiter::canPass(), 'non-IP address');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is to fast, but non-IP address matches exempted range');
    }
}
