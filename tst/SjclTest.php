<?php

use Eris\Generator;
use PrivateBin\Sjcl;

class SjclTest extends PHPUnit_Framework_TestCase
{
    use Eris\TestTrait;

    public function testSjclValidatorValidatesCorrectly()
    {
        $this->minimumEvaluationRatio(0.01)->forAll(
            Helper::getPasteGenerator(array(), true),
            Generator\string(),
            Generator\string(),
            Generator\choose(0,100)
        )->then(
            function ($pasteArray, $key, $value, $lowInt) {
                $paste = Helper::getPasteFromGeneratedArray($pasteArray);
                $this->assertTrue(Sjcl::isValid($paste['data']), 'valid sjcl');
                $this->assertTrue(Sjcl::isValid($paste['attachment']), 'valid sjcl');
                $this->assertTrue(Sjcl::isValid($paste['attachmentname']), 'valid sjcl');

                // common error cases
                $this->assertFalse(Sjcl::isValid($value), 'non-json data');

                $sjclArray = json_decode($paste['data'], true);
                $sjclError = $sjclArray;
                $sjclError['iv'] = '$' . $value;
                $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'invalid base64 encoding of iv');

                $sjclError = $sjclArray;
                $sjclError['salt'] = '$' . $value;
                $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'invalid base64 encoding of salt');

                $sjclError = $sjclArray;
                $sjclError['ct'] = '$' . $value;
                $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'invalid base64 encoding of ct');

                $sjclError = $sjclArray;
                $sjclError['ct'] = 'bm9kYXRhbm9kYXRhbm9kYXRhbm9kYXRhbm9kYXRhCg==';
                $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'low ct entropy');

                $sjclError = $sjclArray;
                $sjclError['iv'] = 'MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=';
                $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'iv to long');

                $sjclError = $sjclArray;
                $sjclError['salt'] = 'MTIzNDU2Nzg5MDEyMzQ1Njc4OTA=';
                $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'salt to long');

                $sjclError = $sjclArray;
                $sjclError[$key] = $value;
                $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'invalid additional key');

                if (!in_array($key, array('1', 'ccm', 'ocb2', 'gcm', 'aes'))) {
                    $sjclError = $sjclArray;
                    $sjclError['v'] = $key;
                    $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'unsupported version');

                    $sjclError = $sjclArray;
                    $sjclError['mode'] = $key;
                    $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'invalid mode');

                    $sjclError = $sjclArray;
                    $sjclError['cipher'] = $key;
                    $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'invalid cipher');
                }

                $sjclError = $sjclArray;
                $sjclError['iter'] = $lowInt;
                $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'not enough iterations');

                if (!in_array($lowInt, array(64, 96))) {
                    $sjclError = $sjclArray;
                    $sjclError['ks'] = $lowInt;
                    $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'invalid key size');

                    $sjclError = $sjclArray;
                    $sjclError['ts'] = $lowInt;
                    $this->assertFalse(Sjcl::isValid(json_encode($sjclError)), 'invalid authentication strength');
                }
                // @note adata is not validated, except as part of the total message length
            }
        );
        $this->assertTrue(Sjcl::isValid(Helper::getComment()['data']), 'valid sjcl');
    }
}
