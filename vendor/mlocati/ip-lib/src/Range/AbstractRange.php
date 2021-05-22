<?php

namespace IPLib\Range;

use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
use IPLib\Address\IPv6;
use IPLib\Address\Type as AddressType;
use IPLib\Factory;

abstract class AbstractRange implements RangeInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getRangeType()
     */
    public function getRangeType()
    {
        if ($this->rangeType === null) {
            $addressType = $this->getAddressType();
            if ($addressType === AddressType::T_IPv6 && Subnet::get6to4()->containsRange($this)) {
                $this->rangeType = Factory::rangeFromBoundaries($this->fromAddress->toIPv4(), $this->toAddress->toIPv4())->getRangeType();
            } else {
                switch ($addressType) {
                    case AddressType::T_IPv4:
                        $defaultType = IPv4::getDefaultReservedRangeType();
                        $reservedRanges = IPv4::getReservedRanges();
                        break;
                    case AddressType::T_IPv6:
                        $defaultType = IPv6::getDefaultReservedRangeType();
                        $reservedRanges = IPv6::getReservedRanges();
                        break;
                    default:
                        throw new \Exception('@todo'); // @codeCoverageIgnore
                }
                $rangeType = null;
                foreach ($reservedRanges as $reservedRange) {
                    $rangeType = $reservedRange->getRangeType($this);
                    if ($rangeType !== null) {
                        break;
                    }
                }
                $this->rangeType = $rangeType === null ? $defaultType : $rangeType;
            }
        }

        return $this->rangeType === false ? null : $this->rangeType;
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
            $cmp = $address->getComparableString();
            $from = $this->getComparableStartString();
            if ($cmp >= $from) {
                $to = $this->getComparableEndString();
                if ($cmp <= $to) {
                    $result = true;
                }
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
            $myStart = $this->getComparableStartString();
            $itsStart = $range->getComparableStartString();
            if ($itsStart >= $myStart) {
                $myEnd = $this->getComparableEndString();
                $itsEnd = $range->getComparableEndString();
                if ($itsEnd <= $myEnd) {
                    $result = true;
                }
            }
        }

        return $result;
    }
}
