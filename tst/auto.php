<?php
class autoTest extends PHPUnit_Framework_TestCase
{
    public function testAutoloaderReturnsFalseWhenCallingNonExistingClass()
    {
        $this->assertFalse(auto::loader('foo2501bar42'), 'calling non existent class');
    }
}
