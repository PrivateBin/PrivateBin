<?php

namespace IPLib\Service;

use InvalidArgumentException;

/**
 * @internal
 *
 * @readonly
 */
class NumberInChunks
{
    const CHUNKSIZE_BYTES = 8;

    const CHUNKSIZE_WORDS = 16;

    /**
     * @var bool
     */
    public $negative;

    /**
     * @var int[]
     */
    public $chunks;

    /**
     * @var int
     */
    public $chunkSize;

    /**
     * @param bool $negative
     * @param int[] $chunks
     * @param int $chunkSize
     */
    public function __construct($negative, array $chunks, $chunkSize)
    {
        $this->negative = $negative;
        $this->chunks = $chunks;
        $this->chunkSize = $chunkSize;
    }

    /**
     * @throws \InvalidArgumentException if $other has a $chunkSize that's not the same as the $chunkSize of this
     *
     * @return \IPLib\Service\NumberInChunks
     */
    public function negate()
    {
        return new self($this->chunks === array(0) ? false : !$this->negative, $this->chunks, $this->chunkSize);
    }

    /**
     * @throws \InvalidArgumentException if $other has a $chunkSize that's not the same as the $chunkSize of this
     *
     * @return \IPLib\Service\NumberInChunks
     */
    public function add(NumberInChunks $that)
    {
        if ($this->chunkSize !== $that->chunkSize) {
            throw new InvalidArgumentException('Incompatible chunk size');
        }
        if ($this->negative === $that->negative) {
            return new self($this->negative, self::addChunks($this->chunks, $that->chunks, $this->chunkSize), $this->chunkSize);
        }
        if ($that->negative) {
            list($negative, $chunks) = self::substractChunks($this->chunks, $that->chunks, $this->chunkSize);
        } else {
            list($negative, $chunks) = self::substractChunks($that->chunks, $this->chunks, $this->chunkSize);
        }

        return new self($negative, $chunks, $this->chunkSize);
    }

    /**
     * @param int $int
     * @param int $chunkSize
     *
     * @return \IPLib\Service\NumberInChunks
     */
    public static function fromInteger($int, $chunkSize)
    {
        if ($int === 0) {
            return new self(false, array(0), $chunkSize);
        }
        $negative = $int < 0;
        if ($negative) {
            $positiveInt = -$int;
            /** @var int|float $positiveInt may be float because -PHP_INT_MIN is bigger than PHP_INT_MAX */
            if (is_float($positiveInt)) {
                return self::fromNumericString((string) $int, $chunkSize);
            }
            $int = $positiveInt;
        }
        $bitMask = (1 << $chunkSize) - 1;
        $chunks = array();
        while ($int !== 0) {
            $chunks[] = $int & $bitMask;
            $int >>= $chunkSize;
        }

        return new self($negative, array_reverse($chunks), $chunkSize);
    }

    /**
     * @param string $numericString a string normalized with BinaryMath::normalizeIntegerString()
     * @param int $chunkSize
     *
     * @return \IPLib\Service\NumberInChunks
     */
    public static function fromNumericString($numericString, $chunkSize)
    {
        if ($numericString === '0') {
            return new self(false, array(0), $chunkSize);
        }
        $negative = $numericString[0] === '-';
        if ($negative) {
            $numericString = substr($numericString, 1);
        }
        $chunks = array();
        while ($numericString !== '0') {
            $chunks[] = self::modulo($numericString, $chunkSize);
            $numericString = self::divide($numericString, $chunkSize);
        }

        return new self($negative, array_reverse($chunks), $chunkSize);
    }

    /**
     * @param string $numericString
     * @param int $chunkSize
     *
     * @return int
     */
    private static function modulo($numericString, $chunkSize)
    {
        $divisor = 1 << $chunkSize;
        $carry = 0;
        $len = strlen($numericString);
        for ($i = 0; $i < $len; $i++) {
            $digit = (int) $numericString[$i];
            $carry = ($carry * 10 + $digit) % $divisor;
        }

        return $carry;
    }

    /**
     * @param string $numericString
     * @param int $chunkSize
     *
     * @return string
     */
    private static function divide($numericString, $chunkSize)
    {
        $divisor = 1 << $chunkSize;
        $quotient = '';
        $carry = 0;
        $len = strlen($numericString);
        for ($i = 0; $i < $len; $i++) {
            $digit = (int) $numericString[$i];
            $value = $carry * 10 + $digit;
            $quotient .= (string) ($value >> $chunkSize);
            $carry = $value % $divisor;
        }

        return ltrim($quotient, '0') ?: '0';
    }

    /**
     * @param int[] $addend1
     * @param int[] $addend2
     * @param int $chunkSize
     *
     * @return int[]
     */
    private static function addChunks(array $addend1, array $addend2, $chunkSize)
    {
        $divisor = 1 << $chunkSize;
        $result = array();
        $carry = 0;
        while ($addend1 !== array() || $addend2 !== array()) {
            $sum = $carry + (array_pop($addend1) ?: 0) + (array_pop($addend2) ?: 0);
            $result[] = $sum % $divisor;
            $carry = $sum >> $chunkSize;
        }
        if ($carry !== 0) {
            $result[] = $carry;
        }

        return array_reverse($result);
    }

    /**
     * @param int[] $minuend
     * @param int[] $subtrahend
     * @param int $chunkSize
     *
     * @return array{bool, int[]}
     */
    private static function substractChunks(array $minuend, array $subtrahend, $chunkSize)
    {
        $minuendCount = count($minuend);
        $subtrahendCount = count($subtrahend);
        if ($minuendCount > $subtrahendCount) {
            $count = $minuendCount;
            $negative = false;
        } elseif ($minuendCount < $subtrahendCount) {
            $count = $subtrahendCount;
            $negative = true;
        } else {
            $count = $minuendCount;
            $negative = false;
            for ($i = 0; $i < $count; $i++) {
                $delta = $minuend[$i] - $subtrahend[$i];
                if ($delta === 0) {
                    continue;
                }
                if ($delta < 0) {
                    $negative = true;
                }
                break;
            }
        }
        if ($negative) {
            list($minuend, $subtrahend) = array($subtrahend, $minuend);
        }
        $subtrahend = array_pad($subtrahend, -$count, 0);
        $borrowValue = 1 << $chunkSize;
        $result = array();
        $borrow = 0;
        for ($i = $count - 1; $i >= 0; $i--) {
            $value = $minuend[$i] - $subtrahend[$i] - $borrow;
            if ($value < 0) {
                $value += $borrowValue;
                $borrow = 1;
            } else {
                $borrow = 0;
            }
            $result[] = $value;
        }
        while (isset($result[1])) {
            $value = array_pop($result);
            if ($value !== 0) {
                $result[] = $value;
                break;
            }
        }

        return array($negative, array_reverse($result));
    }
}
