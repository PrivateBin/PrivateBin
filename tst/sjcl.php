<?php
class sjclTest extends PHPUnit_Framework_TestCase
{
    public function testSjclValidatorValidatesCorrectly()
    {
        $this->assertTrue(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'valid sjcl');
        $this->assertFalse(sjcl::isValid('{"iv":"$","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid base64 encoding of iv');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","salt":"$","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'invalid base64 encoding of salt');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","salt":"Gx1vA2/gQ3U","ct":"$"}'), 'invalid base64 encoding of ct');
        $this->assertFalse(sjcl::isValid('{"iv":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'iv to long');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","salt":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=","ct":"j7ImByuE5xCqD2YXm6aSyA"}'), 'salt to long');
        $this->assertFalse(sjcl::isValid('{"iv":"83Ax/OdUav3SanDW9dcQPg","salt":"Gx1vA2/gQ3U","ct":"j7ImByuE5xCqD2YXm6aSyA","foo":"MTIzNDU2Nzg5MDEyMzQ1Njc4OTA="}'), 'invalid additional key');
    }
}
