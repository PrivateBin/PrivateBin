<?php

define('MCRYPT_DEV_URANDOM', 1);

function mcrypt_create_iv($int, $flag)
{
    $randomSalt = '';
    for($i = 0; $i < 16; ++$i) {
        $randomSalt .= base_convert(mt_rand(), 10, 16);
    }
    // hex2bin requires an even length, pad if necessary
    if (strlen($randomSalt) % 2)
    {
        $randomSalt = '0' . $randomSalt;
    }
    return hex2bin($randomSalt);
}
