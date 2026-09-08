<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\FormatV2;
use PrivateBin\FormatV3;

class FormatV3Test extends TestCase
{
    private function getPaste()
    {
        $paste = Helper::getPastePost();
        $paste['v'] = 3;
        $paste['adata'][4] = [
            'type'       => 'recipient',
            'suite'      => 'ML-KEM-768+HKDF-SHA-256+AES-256-GCM',
            'kid'        => 'AbCdEfGhIjKlMnOp',
            'kemct'      => base64_encode(random_bytes(1088)),
            'salt'       => base64_encode(random_bytes(32)),
            'iv'         => base64_encode(random_bytes(12)),
            'wrappedkey' => base64_encode(random_bytes(48)),
        ];
        return $paste;
    }

    public function testValidRecipientEnvelope()
    {
        $paste = $this->getPaste();
        $this->assertTrue(FormatV3::isValid($paste));
        $this->assertFalse(FormatV2::isValid($paste), 'v3 must not silently pass the v2 validator');
    }

    public function testRejectsMalformedEnvelope()
    {
        foreach (['type', 'suite', 'kid', 'kemct', 'salt', 'iv', 'wrappedkey'] as $field) {
            $paste = $this->getPaste();
            unset($paste['adata'][4][$field]);
            $this->assertFalse(FormatV3::isValid($paste), 'missing ' . $field);
        }

        $paste = $this->getPaste();
        $paste['adata'][4]['kemct'] = base64_encode(random_bytes(1087));
        $this->assertFalse(FormatV3::isValid($paste), 'wrong ML-KEM ciphertext size');

        $paste = $this->getPaste();
        $paste['adata'][4]['wrappedkey'] = '$';
        $this->assertFalse(FormatV3::isValid($paste), 'invalid wrapped-key base64');
    }

    public function testRejectsCommentsAndOtherVersions()
    {
        $paste = $this->getPaste();
        $this->assertFalse(FormatV3::isValid($paste, true));

        $paste['v'] = 2;
        $this->assertFalse(FormatV3::isValid($paste));
    }
}
