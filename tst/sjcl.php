<?php
class sjclTest extends PHPUnit_Framework_TestCase
{
    public function testSjclValidatorValidatesCorrectly()
    {
        $paste = helper::getPasteWithAttachment();
        $this->assertTrue(sjcl::isValid($paste['data']), 'valid sjcl');
        $this->assertTrue(sjcl::isValid($paste['attachment']), 'valid sjcl');
        $this->assertTrue(sjcl::isValid($paste['attachmentname']), 'valid sjcl');
        $this->assertTrue(sjcl::isValid(helper::getComment()['data']), 'valid sjcl');

        $this->assertTrue(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'valid sjcl');
        $this->assertFalse(sjcl::isValid('{"iv":"$","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid base64 encoding of iv');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"$","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid base64 encoding of salt');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","salt":"Gx1vA2/gQ3U","ct":"$"}'), 'invalid base64 encoding of ct');
        $this->assertFalse(sjcl::isValid('{"iv":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'iv to long');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'salt to long');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA","foo":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA="}'), 'invalid additional key');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":0.9,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'unsupported version');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":100,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'not enough iterations');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":127,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid key size');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":63,"mode":"ccm","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid tag length');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"!#@","adata":"","cipher":"aes","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid mode');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"!#@","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid cipher');
        // @note adata is not validated, except as part of the total message length
    }
}
