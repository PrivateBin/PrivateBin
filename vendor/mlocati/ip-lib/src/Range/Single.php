<?php

namespace IPLib\Range;

use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
use IPLib\Address\Type as AddressType;
use IPLib\Factory;

/**
 * Represents a single address (eg a range that contains just one address).
 *
 * @example 127.0.0.1
 * @example ::1
 */
class Single extends AbstractRange
{
    /**
     * @var \IPLib\Address\AddressInterface
     */
    protected $address;

    /**
     * Initializes the instance.
     *
     * @param \IPLib\Address\AddressInterface $address
     */
    protected function __construct(AddressInterface $address)
    {
        $this->address = $address;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::__toString()
     */
    public function __toString()
    {
        return $this->address->__toString();
    }

    /**
     * Try get the range instance starting from its string representation.
     *
     * @param string|mixed $range
     * @param bool $supportNonDecimalIPv4 set to true to support parsing non decimal (that is, octal and hexadecimal) IPv4 addresses
     *
     * @return static|null
     */
    public static function fromString($range, $supportNonDecimalIPv4 = false)
    {
        $result = null;
        $address = Factory::addressFromString($range, true, true, $supportNonDecimalIPv4);
        if ($address !== null) {
            $result = new static($address);
        }

        return $result;
    }

    /**
     * Create the range instance starting from an address instance.
     *
     * @param \IPLib\Address\AddressInterface $address
     *
     * @return static
     */
    public static function fromAddress(AddressInterface $address)
    {
        return new static($address);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::toString()
     */
    public function toString($long = false)
    {
        return $this->address->toString($long);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getAddressType()
     */
    public function getAddressType()
    {
        return $this->address->getAddressType();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getRangeType()
     */
    public function getRangeType()
    {
        return $this->address->getRangeType();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::contains()
     */
    public function contains(AddressInterface $address)
    {
        $result = false;
        if ($address->getAddressType() === $this->getAddressType()) {
            if ($address->toString(false) === $this->address->toString(false)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::containsRange()
     */
    public function containsRange(RangeInterface $range)
    {
        $result = false;
        if ($range->getAddressType() === $this->getAddressType()) {
            if ($range->toString(false) === $this->toString(false)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getStartAddress()
     */
    public function getStartAddress()
    {
        return $this->address;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getEndAddress()
     */
    public function getEndAddress()
    {
        return $this->address;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getComparableStartString()
     */
    public function getComparableStartString()
    {
        return $this->address->getComparableString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getComparableEndString()
     */
    public function getComparableEndString()
    {
        return $this->address->getComparableString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::asSubnet()
     */
    public function asSubnet()
    {
        $networkPrefixes = array(
            AddressType::T_IPv4 => 32,
            AddressType::T_IPv6 => 128,
        );

        return new Subnet($this->address, $this->address, $networkPrefixes[$this->address->getAddressType()]);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::asPattern()
     */
    public function asPattern()
    {
        return new Pattern($this->address, $this->address, 0);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getSubnetMask()
     */
    public function getSubnetMask()
    {
        if ($this->getAddressType() !== AddressType::T_IPv4) {
            return null;
        }

        return IPv4::fromBytes(array(255, 255, 255, 255));
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getReverseDNSLookupName()
     */
    public function getReverseDNSLookupName()
    {
        return array($this->getStartAddress()->getReverseDNSLookupName());
    }
}
