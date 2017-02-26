<?php

use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Vizhash16x16;
use Eris\Generator;

class Vizhash16x16Test extends PHPUnit_Framework_TestCase
{
    use Eris\TestTrait;

    private $_file;

    private $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        $this->_file = $this->_path . DIRECTORY_SEPARATOR . 'vizhash.png';
        ServerSalt::setPath($this->_path);
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        chmod($this->_path, 0700);
        Helper::rmDir($this->_path);
    }

    public function testVizhashGeneratesPngs()
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )->then(
            function ($string1, $string2)
            {
                $vz      = new Vizhash16x16();
                $pngdata = $vz->generate($string1);

                if (empty($string1))
                {
                    $this->assertEquals($pngdata, '');
                } else {
                    $this->assertNotEquals($pngdata, '');
                    file_put_contents($this->_file, $pngdata);
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $this->assertEquals('image/png', $finfo->file($this->_file));
                    if ($string1 !== $string2)
                    {
                        $this->assertNotEquals($pngdata, $string2);
                    }
                }
                $this->assertEquals($pngdata, $vz->generate($string1));
            }
        );
    }

    public function testVizhashGeneratesUniquePngsPerIpv4Hash()
    {
        $this->forAll(
            Generator\vector(2, Generator\vector(4, Generator\byte()))
        )->then(
            function ($ips)
            {
                $hash1   = hash('sha512', implode('.', $ips[0]));
                $hash2   = hash('sha512', implode('.', $ips[1]));
                $vz      = new Vizhash16x16();
                $pngdata = $vz->generate($hash1);
                file_put_contents($this->_file, $pngdata);
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $this->assertEquals('image/png', $finfo->file($this->_file));
                if ($hash1 !== $hash2)
                {
                    $this->assertNotEquals($pngdata, $vz->generate($hash2));
                }
                $this->assertEquals($pngdata, $vz->generate($hash1));
            }
        );
    }

    public function testVizhashGeneratesUniquePngsPerIpv6Hash()
    {
        $this->forAll(
            Generator\vector(2, Generator\vector(16, Generator\byte()))
        )->then(
            function ($ips)
            {
                $hash1   = hash('sha512', inet_ntop(implode(array_map('chr', $ips[0]))));
                $hash2   = hash('sha512', inet_ntop(implode(array_map('chr', $ips[1]))));
                $vz      = new Vizhash16x16();
                $pngdata = $vz->generate($hash1);
                file_put_contents($this->_file, $pngdata);
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $this->assertEquals('image/png', $finfo->file($this->_file));
                if ($hash1 !== $hash2)
                {
                    $this->assertNotEquals($pngdata, $vz->generate($hash2));
                }
                $this->assertEquals($pngdata, $vz->generate($hash1));
            }
        );
    }
}
