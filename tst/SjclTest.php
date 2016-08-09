<?php

use PrivateBin\Sjcl;

class SjclTest extends PHPUnit_Framework_TestCase
{
    public function testSjclValidatorValidatesCorrectly()
    {
        $paste = Helper::getPasteWithAttachment();
        $this->assertTrue(Sjcl::isValid($paste['data']), 'valid sjcl');
        $this->assertTrue(Sjcl::isValid($paste['attachment']), 'valid sjcl');
        $this->assertTrue(Sjcl::isValid($paste['attachmentname']), 'valid sjcl');
        $this->assertTrue(Sjcl::isValid(Helper::getComment()['data']), 'valid sjcl');

        $this->assertTrue(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'valid sjcl');
        $this->assertFalse(Sjcl::isValid('{"iv":"$","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid base64 encoding of iv');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"$","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid base64 encoding of salt');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"$"}'), 'invalid base64 encoding of ct');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"bm9kYXRhbm9kYXRhbm9kYXRhbm9kYXRhbm9kYXRhCg=="}'), 'low ct entropy');
        $this->assertFalse(Sjcl::isValid('{"iv":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'iv to long');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'salt to long');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA","foo":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA="}'), 'invalid additional key');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":0.9,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'unsupported version');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":100,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'not enough iterations');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":127,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid key size');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":63,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid tag length');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"!#@","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid mode');
        $this->assertFalse(Sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"!#@","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid cipher');
        // @note adata is not validated, except as part of the total message length
    }
}
