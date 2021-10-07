<?php

namespace IPLib\Address;

use IPLib\Range\RangeInterface;
use IPLib\Range\Subnet;
use IPLib\Range\Type as RangeType;

/**
 * An IPv4 address.
 */
class IPv4 implements AddressInterface
{
    /**
     * The string representation of the address.
     *
     * @var string
     *
     * @example '127.0.0.1'
     */
    protected $address;

    /**
     * The byte list of the IP address.
     *
     * @var int[]|null
     */
    protected $bytes;

    /**
     * The type of the range of this IP address.
     *
     * @var int|null
     */
    protected $rangeType;

    /**
     * A string representation of this address than can be used when comparing addresses and ranges.
     *
     * @var string
     */
    protected $comparableString;

    /**
     * An array containing RFC designated address ranges.
     *
     * @var array|null
     */
    private static $reservedRanges = null;

    /**
     * Initializes the instance.
     *
     * @param string $address
     */
    protected function __construct($address)
    {
        $this->address = $address;
        $this->bytes = null;
        $this->rangeType = null;
        $this->comparableString = null;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::__toString()
     */
    public function __toString()
    {
        return $this->address;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getNumberOfBits()
     */
    public static function getNumberOfBits()
    {
        return 32;
    }

    /**
     * Parse a string and returns an IPv4 instance if the string is valid, or null otherwise.
     *
     * @param string|mixed $address the address to parse
     * @param bool $mayIncludePort set to false to avoid parsing addresses with ports
     * @param bool $supportNonDecimalIPv4 set to true to support parsing non decimal (that is, octal and hexadecimal) IPv4 addresses
     *
     * @return static|null
     */
    public static function fromString($address, $mayIncludePort = true, $supportNonDecimalIPv4 = false)
    {
        if (!is_string($address) || !strpos($address, '.')) {
            return null;
        }
        $rxChunk = '0?[0-9]{1,3}';
        if ($supportNonDecimalIPv4) {
            $rxChunk = "(?:0[Xx]0*[0-9A-Fa-f]{1,2})|(?:{$rxChunk})";
        }
        $rx = "0*?({$rxChunk})\.0*?({$rxChunk})\.0*?({$rxChunk})\.0*?({$rxChunk})";
        if ($mayIncludePort) {
            $rx .= '(?::\d+)?';
        }
        $matches = null;
        if (!preg_match('/^' . $rx . '$/', $address, $matches)) {
            return null;
        }
        $nums = array();
        for ($i = 1; $i <= 4; $i++) {
            $s = $matches[$i];
            if ($supportNonDecimalIPv4) {
                if (stripos($s, '0x') === 0) {
                    $n = hexdec(substr($s, 2));
                } elseif ($s[0] === '0') {
                    if (!preg_match('/^[0-7]+$/', $s)) {
                        return null;
                    }
                    $n = octdec(substr($s, 1));
                } else {
                    $n = (int) $s;
                }
            } else {
                $n = (int) $s;
            }
            if ($n < 0 || $n > 255) {
                return null;
            }
            $nums[] = (string) $n;
        }

        return new static(implode('.', $nums));
    }

    /**
     * Parse an array of bytes and returns an IPv4 instance if the array is valid, or null otherwise.
     *
     * @param int[]|array $bytes
     *
     * @return static|null
     */
    public static function fromBytes(array $bytes)
    {
        $result = null;
        if (count($bytes) === 4) {
            $chunks = array_map(
                function ($byte) {
                    return (is_int($byte) && $byte >= 0 && $byte <= 255) ? (string) $byte : false;
                },
                $bytes
            );
            if (in_array(false, $chunks, true) === false) {
                $result = new static(implode('.', $chunks));
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::toString()
     */
    public function toString($long = false)
    {
        if ($long) {
            return $this->getComparableString();
        }

        return $this->address;
    }

    /**
     * Get the octal representation of this IP address.
     *
     * @param bool $long
     *
     * @return string
     *
     * @example if $long == false: if the decimal representation is '0.7.8.255': '0.7.010.0377'
     * @example if $long == true: if the decimal representation is '0.7.8.255': '0000.0007.0010.0377'
     */
    public function toOctal($long = false)
    {
        $chunks = array();
        foreach ($this->getBytes() as $byte) {
            if ($long) {
                $chunks[] = sprintf('%04o', $byte);
            } else {
                $chunks[] = '0' . decoct($byte);
            }
        }

        return implode('.', $chunks);
    }

    /**
     * Get the hexadecimal representation of this IP address.
     *
     * @param bool $long
     *
     * @return string
     *
     * @example if $long == false: if the decimal representation is '0.9.10.255': '0.9.0xa.0xff'
     * @example if $long == true: if the decimal representation is '0.9.10.255': '0x00.0x09.0x0a.0xff'
     */
    public function toHexadecimal($long = false)
    {
        $chunks = array();
        foreach ($this->getBytes() as $byte) {
            if ($long) {
                $chunks[] = sprintf('0x%02x', $byte);
            } else {
                $chunks[] = '0x' . dechex($byte);
            }
        }

        return implode('.', $chunks);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getBytes()
     */
    public function getBytes()
    {
        if ($this->bytes === null) {
            $this->bytes = array_map(
                function ($chunk) {
                    return (int) $chunk;
                },
                explode('.', $this->address)
            );
        }

        return $this->bytes;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getBits()
     */
    public function getBits()
    {
        $parts = array();
        foreach ($this->getBytes() as $byte) {
            $parts[] = sprintf('%08b', $byte);
        }

        return implode('', $parts);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getAddressType()
     */
    public function getAddressType()
    {
        return Type::T_IPv4;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getDefaultReservedRangeType()
     */
    public static function getDefaultReservedRangeType()
    {
        return RangeType::T_PUBLIC;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getReservedRanges()
     */
    public static function getReservedRanges()
    {
        if (self::$reservedRanges === null) {
            $reservedRanges = array();
            foreach (array(
                // RFC 5735
                '0.0.0.0/8' => array(RangeType::T_THISNETWORK, array('0.0.0.0/32' => RangeType::T_UNSPECIFIED)),
                // RFC 5735
                '10.0.0.0/8' => array(RangeType::T_PRIVATENETWORK),
                // RFC 6598
                '100.64.0.0/10' => array(RangeType::T_CGNAT),
                // RFC 5735
                '127.0.0.0/8' => array(RangeType::T_LOOPBACK),
                // RFC 5735
                '169.254.0.0/16' => array(RangeType::T_LINKLOCAL),
                // RFC 5735
                '172.16.0.0/12' => array(RangeType::T_PRIVATENETWORK),
                // RFC 5735
                '192.0.0.0/24' => array(RangeType::T_RESERVED),
                // RFC 5735
                '192.0.2.0/24' => array(RangeType::T_RESERVED),
                // RFC 5735
                '192.88.99.0/24' => array(RangeType::T_ANYCASTRELAY),
                // RFC 5735
                '192.168.0.0/16' => array(RangeType::T_PRIVATENETWORK),
                // RFC 5735
                '198.18.0.0/15' => array(RangeType::T_RESERVED),
                // RFC 5735
                '198.51.100.0/24' => array(RangeType::T_RESERVED),
                // RFC 5735
                '203.0.113.0/24' => array(RangeType::T_RESERVED),
                // RFC 5735
                '224.0.0.0/4' => array(RangeType::T_MULTICAST),
                // RFC 5735
                '240.0.0.0/4' => array(RangeType::T_RESERVED, array('255.255.255.255/32' => RangeType::T_LIMITEDBROADCAST)),
            ) as $range => $data) {
                $exceptions = array();
                if (isset($data[1])) {
                    foreach ($data[1] as $exceptionRange => $exceptionType) {
                        $exceptions[] = new AssignedRange(Subnet::fromString($exceptionRange), $exceptionType);
                    }
                }
                $reservedRanges[] = new AssignedRange(Subnet::fromString($range), $data[0], $exceptions);
            }
            self::$reservedRanges = $reservedRanges;
        }

        return self::$reservedRanges;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getRangeType()
     */
    public function getRangeType()
    {
        if ($this->rangeType === null) {
            $rangeType = null;
            foreach (static::getReservedRanges() as $reservedRange) {
                $rangeType = $reservedRange->getAddressType($this);
                if ($rangeType !== null) {
                    break;
                }
            }
            $this->rangeType = $rangeType === null ? static::getDefaultReservedRangeType() : $rangeType;
        }

        return $this->rangeType;
    }

    /**
     * Create an IPv6 representation of this address (in 6to4 notation).
     *
     * @return \IPLib\Address\IPv6
     */
    public function toIPv6()
    {
        $myBytes = $this->getBytes();

        return IPv6::fromString('2002:' . sprintf('%02x', $myBytes[0]) . sprintf('%02x', $myBytes[1]) . ':' . sprintf('%02x', $myBytes[2]) . sprintf('%02x', $myBytes[3]) . '::');
    }

    /**
     * Create an IPv6 representation of this address (in IPv6 IPv4-mapped notation).
     *
     * @return \IPLib\Address\IPv6
     */
    public function toIPv6IPv4Mapped()
    {
        return IPv6::fromBytes(array_merge(array(0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xff, 0xff), $this->getBytes()));
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getComparableString()
     */
    public function getComparableString()
    {
        if ($this->comparableString === null) {
            $chunks = array();
            foreach ($this->getBytes() as $byte) {
                $chunks[] = sprintf('%03d', $byte);
            }
            $this->comparableString = implode('.', $chunks);
        }

        return $this->comparableString;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::matches()
     */
    public function matches(RangeInterface $range)
    {
        return $range->contains($this);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getNextAddress()
     */
    public function getNextAddress()
    {
        $overflow = false;
        $bytes = $this->getBytes();
        for ($i = count($bytes) - 1; $i >= 0; $i--) {
            if ($bytes[$i] === 255) {
                if ($i === 0) {
                    $overflow = true;
                    break;
                }
                $bytes[$i] = 0;
            } else {
                $bytes[$i]++;
                break;
            }
        }

        return $overflow ? null : static::fromBytes($bytes);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getPreviousAddress()
     */
    public function getPreviousAddress()
    {
        $overflow = false;
        $bytes = $this->getBytes();
        for ($i = count($bytes) - 1; $i >= 0; $i--) {
            if ($bytes[$i] === 0) {
                if ($i === 0) {
                    $overflow = true;
                    break;
                }
                $bytes[$i] = 255;
            } else {
                $bytes[$i]--;
                break;
            }
        }

        return $overflow ? null : static::fromBytes($bytes);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getReverseDNSLookupName()
     */
    public function getReverseDNSLookupName()
    {
        return implode(
            '.',
            array_reverse($this->getBytes())
        ) . '.in-addr.arpa';
    }
}
