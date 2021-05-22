<?php

namespace IPLib;

use IPLib\Address\AddressInterface;
use IPLib\Range\Subnet;
use IPLib\Service\RangesFromBounradyCalculator;

/**
 * Factory methods to build class instances.
 */
class Factory
{
    /**
     * Parse an IP address string.
     *
     * @param string $address the address to parse
     * @param bool $mayIncludePort set to false to avoid parsing addresses with ports
     * @param bool $mayIncludeZoneID set to false to avoid parsing IPv6 addresses with zone IDs (see RFC 4007)
     * @param bool $supportNonDecimalIPv4 set to true to support parsing non decimal (that is, octal and hexadecimal) IPv4 addresses
     *
     * @return \IPLib\Address\AddressInterface|null
     */
    public static function addressFromString($address, $mayIncludePort = true, $mayIncludeZoneID = true, $supportNonDecimalIPv4 = false)
    {
        $result = null;
        if ($result === null) {
            $result = Address\IPv4::fromString($address, $mayIncludePort, $supportNonDecimalIPv4);
        }
        if ($result === null) {
            $result = Address\IPv6::fromString($address, $mayIncludePort, $mayIncludeZoneID);
        }

        return $result;
    }

    /**
     * Convert a byte array to an address instance.
     *
     * @param int[]|array $bytes
     *
     * @return \IPLib\Address\AddressInterface|null
     */
    public static function addressFromBytes(array $bytes)
    {
        $result = null;
        if ($result === null) {
            $result = Address\IPv4::fromBytes($bytes);
        }
        if ($result === null) {
            $result = Address\IPv6::fromBytes($bytes);
        }

        return $result;
    }

    /**
     * Parse an IP range string.
     *
     * @param string $range
     * @param bool $supportNonDecimalIPv4 set to true to support parsing non decimal (that is, octal and hexadecimal) IPv4 addresses
     *
     * @return \IPLib\Range\RangeInterface|null
     */
    public static function rangeFromString($range, $supportNonDecimalIPv4 = false)
    {
        $result = null;
        if ($result === null) {
            $result = Range\Subnet::fromString($range, $supportNonDecimalIPv4);
        }
        if ($result === null) {
            $result = Range\Pattern::fromString($range, $supportNonDecimalIPv4);
        }
        if ($result === null) {
            $result = Range\Single::fromString($range, $supportNonDecimalIPv4);
        }

        return $result;
    }

    /**
     * Create the smallest address range that comprises two addresses.
     *
     * @param string|\IPLib\Address\AddressInterface $from
     * @param string|\IPLib\Address\AddressInterface $to
     * @param bool $supportNonDecimalIPv4 set to true to support parsing non decimal (that is, octal and hexadecimal) IPv4 addresses
     *
     * @return \IPLib\Range\RangeInterface|null return NULL if $from and/or $to are invalid addresses, or if both are NULL or empty strings, or if they are addresses of different types
     */
    public static function rangeFromBoundaries($from, $to, $supportNonDecimalIPv4 = false)
    {
        list($from, $to) = self::parseBoundaries($from, $to, $supportNonDecimalIPv4);

        return $from === false || $to === false ? null : static::rangeFromBoundaryAddresses($from, $to);
    }

    /**
     * Create a list of Range instances that exactly describes all the addresses between the two provided addresses.
     *
     * @param string|\IPLib\Address\AddressInterface $from
     * @param string|\IPLib\Address\AddressInterface $to
     * @param bool $supportNonDecimalIPv4 set to true to support parsing non decimal (that is, octal and hexadecimal) IPv4 addresses
     *
     * @return \IPLib\Range\Subnet[]|null return NULL if $from and/or $to are invalid addresses, or if both are NULL or empty strings, or if they are addresses of different types
     */
    public static function rangesFromBoundaries($from, $to, $supportNonDecimalIPv4 = false)
    {
        list($from, $to) = self::parseBoundaries($from, $to, $supportNonDecimalIPv4);
        if (($from === false || $to === false) || ($from === null && $to === null)) {
            return null;
        }
        if ($from === null || $to === null) {
            $address = $from ? $from : $to;

            return array(new Subnet($address, $address, $address->getNumberOfBits()));
        }
        $numberOfBits = $from->getNumberOfBits();
        if ($to->getNumberOfBits() !== $numberOfBits) {
            return null;
        }
        $calculator = new RangesFromBounradyCalculator($numberOfBits);

        return $calculator->getRanges($from, $to);
    }

    /**
     * @param \IPLib\Address\AddressInterface $from
     * @param \IPLib\Address\AddressInterface $to
     *
     * @return \IPLib\Range\RangeInterface|null
     */
    protected static function rangeFromBoundaryAddresses(AddressInterface $from = null, AddressInterface $to = null)
    {
        if ($from === null && $to === null) {
            $result = null;
        } elseif ($to === null) {
            $result = Range\Single::fromAddress($from);
        } elseif ($from === null) {
            $result = Range\Single::fromAddress($to);
        } else {
            $result = null;
            $addressType = $from->getAddressType();
            if ($addressType === $to->getAddressType()) {
                $cmp = strcmp($from->getComparableString(), $to->getComparableString());
                if ($cmp === 0) {
                    $result = Range\Single::fromAddress($from);
                } else {
                    if ($cmp > 0) {
                        list($from, $to) = array($to, $from);
                    }
                    $fromBytes = $from->getBytes();
                    $toBytes = $to->getBytes();
                    $numBytes = count($fromBytes);
                    $sameBits = 0;
                    for ($byteIndex = 0; $byteIndex < $numBytes; $byteIndex++) {
                        $fromByte = $fromBytes[$byteIndex];
                        $toByte = $toBytes[$byteIndex];
                        if ($fromByte === $toByte) {
                            $sameBits += 8;
                        } else {
                            $differentBitsInByte = decbin($fromByte ^ $toByte);
                            $sameBits += 8 - strlen($differentBitsInByte);
                            break;
                        }
                    }
                    $result = static::rangeFromString($from->toString(true) . '/' . (string) $sameBits);
                }
            }
        }

        return $result;
    }

    /**
     * @param string|\IPLib\Address\AddressInterface $from
     * @param string|\IPLib\Address\AddressInterface $to
     * @param bool $supportNonDecimalIPv4
     *
     * @return \IPLib\Address\AddressInterface[]|null[]|false[]
     */
    private static function parseBoundaries($from, $to, $supportNonDecimalIPv4 = false)
    {
        $result = array();
        foreach (array('from', 'to') as $param) {
            $value = $$param;
            if (!($value instanceof AddressInterface)) {
                $value = (string) $value;
                if ($value === '') {
                    $value = null;
                } else {
                    $value = static::addressFromString($value, true, true, $supportNonDecimalIPv4);
                    if ($value === null) {
                        $value = false;
                    }
                }
            }
            $result[] = $value;
        }
        if ($result[0] && $result[1] && strcmp($result[0]->getComparableString(), $result[1]->getComparableString()) > 0) {
            $result = array($result[1], $result[0]);
        }

        return $result;
    }
}
