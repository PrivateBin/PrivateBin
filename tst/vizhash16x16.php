<?php
class vizhash16x16Test extends PHPUnit_Framework_TestCase
{
    private $_dataDirCreated;

    private $_file;

    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = PATH . 'data';
        if(!is_dir($this->_path)) mkdir($this->_path);
        $this->_file = $this->_path . DIRECTORY_SEPARATOR . 'vizhash.png';
        serversalt::setPath($this->_path);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        chmod($this->_path, 0700);
        if(!@unlink($this->_file)) {
            throw new Exception('Error deleting file "' . $this->_file . '".');
        }
        helper::rmdir($this->_path);
    }

    public function testVizhashGeneratesUniquePngsPerIp()
    {
        $vz = new vizhash16x16();
        $pngdata = $vz->generate('127.0.0.1');
        file_put_contents($this->_file, $pngdata);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->assertEquals('image/png', $finfo->file($this->_file));
        $this->assertNotEquals($pngdata, $vz->generate('2001:1620:2057:dead:beef::cafe:babe'));
        $this->assertEquals($pngdata, $vz->generate('127.0.0.1'));
    }
}
