<?php

use PHPUnit\Framework\TestCase;
use PrivateBin\FormatV2;

class FormatV2Test extends TestCase
{
    public function testFormatV2ValidatorValidatesCorrectly()
    {
        $this->assertTrue(FormatV2::isValid(Helper::getPastePost()), 'valid format');
        $this->assertTrue(FormatV2::isValid(Helper::getCommentPost(), true), 'valid format');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][0] = '$';
        $this->assertFalse(FormatV2::isValid($paste), 'invalid base64 encoding of iv');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][1] = '$';
        $this->assertFalse(FormatV2::isValid($paste), 'invalid base64 encoding of salt');

        $paste       = Helper::getPastePost();
        $paste['ct'] = '$';
        $this->assertFalse(FormatV2::isValid($paste), 'invalid base64 encoding of ct');

        $paste       = Helper::getPastePost();
        $paste['ct'] = 'bm9kYXRhbm9kYXRhbm9kYXRhbm9kYXRhbm9kYXRhCg==';
        $this->assertFalse(FormatV2::isValid($paste), 'low ct entropy');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][0] = 'MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=';
        $this->assertFalse(FormatV2::isValid($paste), 'iv too long');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][1] = 'MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=';
        $this->assertFalse(FormatV2::isValid($paste), 'salt too long');

        $paste        = Helper::getPastePost();
        $paste['foo'] = 'bar';
        $this->assertFalse(FormatV2::isValid($paste), 'invalid additional key');
        unset($paste['meta']);
        $this->assertFalse(FormatV2::isValid($paste), 'invalid missing key');

        $paste      = Helper::getPastePost();
        $paste['v'] = 0.9;
        $this->assertFalse(FormatV2::isValid($paste), 'unsupported version');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][2] = 1000;
        $this->assertFalse(FormatV2::isValid($paste), 'not enough iterations');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][3] = 127;
        $this->assertFalse(FormatV2::isValid($paste), 'invalid key size');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][4] = 63;
        $this->assertFalse(FormatV2::isValid($paste), 'invalid tag length');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][5] = '!#@';
        $this->assertFalse(FormatV2::isValid($paste), 'invalid algorithm');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][6] = '!#@';
        $this->assertFalse(FormatV2::isValid($paste), 'invalid mode');

        $paste                = Helper::getPastePost();
        $paste['adata'][0][7] = '!#@';
        $this->assertFalse(FormatV2::isValid($paste), 'invalid compression');

        $this->assertFalse(FormatV2::isValid(Helper::getPaste()), 'invalid meta key');
    }
}
