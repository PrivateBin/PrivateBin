<?php

use PrivateBin\Persistence\ServerSalt;

class ServerSaltTest extends PHPUnit_Framework_TestCase
{
    private $_path;

    private $_invalidPath;

    private $_otherPath;

    private $_invalidFile;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        ServerSalt::setPath($this->_path);

        $this->_otherPath = $this->_path . DIRECTORY_SEPARATOR . 'foo';

        $this->_invalidPath = $this->_path . DIRECTORY_SEPARATOR . 'bar';
        if (!is_dir($this->_invalidPath)) {
            mkdir($this->_invalidPath);
        }
        $this->_invalidFile = $this->_invalidPath . DIRECTORY_SEPARATOR . 'salt.php';
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        chmod($this->_invalidPath, 0700);
        Helper::rmDir($this->_path);
    }

    public function testGeneration()
    {
        // generating new salt
        ServerSalt::setPath($this->_path);
        $salt = ServerSalt::get();

        // try setting a different path and resetting it
        ServerSalt::setPath($this->_otherPath);
        $this->assertNotEquals($salt, ServerSalt::get());
        ServerSalt::setPath($this->_path);
        $this->assertEquals($salt, ServerSalt::get());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 11
     */
    public function testPathShenanigans()
    {
        // try setting an invalid path
        chmod($this->_invalidPath, 0000);
        ServerSalt::setPath($this->_invalidPath);
        ServerSalt::get();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 20
     */
    public function testFileRead()
    {
        // try setting an invalid file
        chmod($this->_invalidPath, 0700);
        file_put_contents($this->_invalidFile, '');
        chmod($this->_invalidFile, 0000);
        ServerSalt::setPath($this->_invalidPath);
        ServerSalt::get();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 13
     */
    public function testFileWrite()
    {
        // try setting an invalid file
        chmod($this->_invalidPath, 0700);
        if (is_file($this->_invalidFile)) {
            chmod($this->_invalidFile, 0600);
            unlink($this->_invalidFile);
        }
        file_put_contents($this->_invalidPath . DIRECTORY_SEPARATOR . '.htaccess', '');
        chmod($this->_invalidPath, 0500);
        ServerSalt::setPath($this->_invalidPath);
        ServerSalt::get();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 10
     */
    public function testPermissionShenanigans()
    {
        // try creating an invalid path
        chmod($this->_invalidPath, 0000);
        ServerSalt::setPath($this->_invalidPath . DIRECTORY_SEPARATOR . 'baz');
        ServerSalt::get();
    }
}
