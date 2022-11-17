<?php

use PHPUnit\Framework\TestCase;
use PrivateBin\Data\Filesystem;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Persistence\TrafficLimiter;

class TrafficLimiterTest extends TestCase
{
    private $_path;

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trafficlimit';
        $store       = new Filesystem(array('dir' => $this->_path));
        ServerSalt::setStore($store);
        TrafficLimiter::setStore($store);
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        Helper::rmDir($this->_path . DIRECTORY_SEPARATOR);
    }

    public function testHtaccess()
    {
        $htaccess = $this->_path . DIRECTORY_SEPARATOR . '.htaccess';
        @unlink($htaccess);
        $_SERVER['REMOTE_ADDR'] = 'foobar';
        TrafficLimiter::canPass();
        $this->assertFileExists($htaccess, 'htaccess recreated');
    }

    public function testTrafficGetsLimited()
    {
        TrafficLimiter::setLimit(4);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertTrue(TrafficLimiter::canPass(), 'first request may pass');
        sleep(1);
        try {
            $this->assertFalse(TrafficLimiter::canPass(), 'expected an exception');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Please wait 4 seconds between each post.', 'second request is to fast, may not pass');
        }
        sleep(4);
        $this->assertTrue(TrafficLimiter::canPass(), 'third request waited long enough and may pass');
        $_SERVER['REMOTE_ADDR'] = '2001:1620:2057:dead:beef::cafe:babe';
        $this->assertTrue(TrafficLimiter::canPass(), 'fourth request has different ip and may pass');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        try {
            $this->assertFalse(TrafficLimiter::canPass(), 'expected an exception');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Please wait 4 seconds between each post.', 'fifth request is to fast, may not pass');
        }
    }

    public function testTrafficLimitExempted()
    {
        TrafficLimiter::setExempted('1.2.3.4,10.10.10/24,2001:1620:2057::/48');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertTrue(TrafficLimiter::canPass(), 'first request may pass');
        try {
            $this->assertFalse(TrafficLimiter::canPass(), 'expected an exception');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Please wait 4 seconds between each post.', 'not exempted');
        }
        $_SERVER['REMOTE_ADDR'] = '10.10.10.10';
        $this->assertTrue(TrafficLimiter::canPass(), 'IPv4 in exempted range');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is to fast, but IPv4 in exempted range');
        $_SERVER['REMOTE_ADDR'] = '2001:1620:2057:dead:beef::cafe:babe';
        $this->assertTrue(TrafficLimiter::canPass(), 'IPv6 in exempted range');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is to fast, but IPv6 in exempted range');
        TrafficLimiter::setExempted('127.*,foobar');
        $this->assertTrue(TrafficLimiter::canPass(), 'first cached request may pass');
        try {
            $this->assertFalse(TrafficLimiter::canPass(), 'expected an exception');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Please wait 4 seconds between each post.', 'request is too fast, invalid range');
        }
        $_SERVER['REMOTE_ADDR'] = 'foobar';
        $this->assertTrue(TrafficLimiter::canPass(), 'non-IP address');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is too fast, but non-IP address matches exempted range');
    }

    public function testTrafficLimitCreators()
    {
        TrafficLimiter::setCreators('1.2.3.4,10.10.10/24,2001:1620:2057::/48');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        try {
            $this->assertFalse(TrafficLimiter::canPass(), 'expected an exception');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Your IP is not authorized to create pastes.', 'not a creator');
        }
        $_SERVER['REMOTE_ADDR'] = '10.10.10.10';
        $this->assertTrue(TrafficLimiter::canPass(), 'IPv4 in creator range');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is too fast, but IPv4 in creator range');
        $_SERVER['REMOTE_ADDR'] = '2001:1620:2057:dead:beef::cafe:babe';
        $this->assertTrue(TrafficLimiter::canPass(), 'IPv6 in creator range');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is too fast, but IPv6 in creator range');
        TrafficLimiter::setCreators('127.*,foobar');
        try {
            $this->assertFalse(TrafficLimiter::canPass(), 'expected an exception');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Your IP is not authorized to create pastes.', 'request is to fast, not a creator');
        }
        $_SERVER['REMOTE_ADDR'] = 'foobar';
        $this->assertTrue(TrafficLimiter::canPass(), 'non-IP address');
        $this->assertTrue(TrafficLimiter::canPass(), 'request is to fast, but non-IP address matches creator');
    }
}
