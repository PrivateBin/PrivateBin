<?php
class serversaltTest extends PHPUnit_Framework_TestCase
{
    private $_path;

    private $_invalidPath;

    private $_otherPath;

    private $_invalidFile;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = PATH . 'data';
        if(!is_dir($this->_path)) mkdir($this->_path);
        serversalt::setPath($this->_path);

        $this->_otherPath = $this->_path . DIRECTORY_SEPARATOR . 'foo';

        $this->_invalidPath = $this->_path . DIRECTORY_SEPARATOR . 'bar';
        if(!is_dir($this->_invalidPath)) mkdir($this->_invalidPath);
        $this->_invalidFile = $this->_invalidPath . DIRECTORY_SEPARATOR . 'salt.php';
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        chmod($this->_invalidPath, 0700);
        helper::rmdir($this->_path);
    }

    public function testGeneration()
    {
        // generating new salt
        serversalt::setPath($this->_path);
        $salt = serversalt::get();

        // mcrypt mock
        if (!defined('MCRYPT_DEV_URANDOM')) define('MCRYPT_DEV_URANDOM', 1);
        function mcrypt_create_iv($int, $flag)
        {
            $randomSalt = '';
            for($i = 0; $i < 256; ++$i) {
                $randomSalt .= base_convert(mt_rand(), 10, 16);
            }
            // hex2bin requires an even length, pad if necessary
            if (strlen($randomSalt) % 2)
            {
                $randomSalt = '0' . $randomSalt;
            }
            return hex2bin($randomSalt);
        }
        $this->assertNotEquals($salt, serversalt::generate());

        // try setting a different path and resetting it
        serversalt::setPath($this->_otherPath);
        $this->assertNotEquals($salt, serversalt::get());
        serversalt::setPath($this->_path);
        $this->assertEquals($salt, serversalt::get());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 11
     */
    public function testPathShenanigans()
    {
        // try setting an invalid path
        chmod($this->_invalidPath, 0000);
        serversalt::setPath($this->_invalidPath);
        serversalt::get();
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
        serversalt::setPath($this->_invalidPath);
        serversalt::get();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 13
     */
    public function testFileWrite()
    {
        // try setting an invalid file
        chmod($this->_invalidPath, 0700);
        @unlink($this->_invalidFile);
        file_put_contents($this->_invalidPath . DIRECTORY_SEPARATOR . '.htaccess', '');
        chmod($this->_invalidPath, 0500);
        serversalt::setPath($this->_invalidPath);
        serversalt::get();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 10
     */
    public function testPermissionShenanigans()
    {
        // try creating an invalid path
        chmod($this->_invalidPath, 0000);
        serversalt::setPath($this->_invalidPath . DIRECTORY_SEPARATOR . 'baz');
        serversalt::get();
    }
}
