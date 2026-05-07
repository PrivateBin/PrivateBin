<?php

namespace IPLib\Service;

/**
 * Helper class to work with unsigned binary integers.
 *
 * @internal
 */
class BinaryMath
{
    /**
     * @var \IPLib\Service\BinaryMath|null
     */
    private static $instance;

    /**
     * @return \IPLib\Service\BinaryMath
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Trim the leading zeroes from a non-negative integer represented in binary form.
     *
     * @param string $value
     *
     * @return string
     */
    public function reduce($value)
    {
        $value = ltrim($value, '0');

        return $value === '' ? '0' : $value;
    }

    /**
     * Compare two non-negative integers represented in binary form.
     *
     * @param string $a
     * @param string $b
     *
     * @return int 1 if $a is greater than $b, -1 if $b is greater than $b, 0 if they are the same
     */
    public function compare($a, $b)
    {
        list($a, $b) = $this->toSameLength($a, $b);

        return $a < $b ? -1 : ($a > $b ? 1 : 0);
    }

    /**
     * Add 1 to a non-negative integer represented in binary form.
     *
     * @param string $value
     *
     * @return string
     */
    public function increment($value)
    {
        $lastZeroIndex = strrpos($value, '0');
        if ($lastZeroIndex === false) {
            return '1' . str_repeat('0', strlen($value));
        }

        return ltrim(substr($value, 0, $lastZeroIndex), '0') . '1' . str_repeat('0', strlen($value) - $lastZeroIndex - 1);
    }

    /**
     * Calculate the bitwise AND of two non-negative integers represented in binary form.
     *
     * @param string $operand1
     * @param string $operand2
     *
     * @return string
     */
    public function andX($operand1, $operand2)
    {
        $operand1 = $this->reduce($operand1);
        $operand2 = $this->reduce($operand2);
        $numBits = min(strlen($operand1), strlen($operand2));
        $operand1 = substr(str_pad($operand1, $numBits, '0', STR_PAD_LEFT), -$numBits);
        $operand2 = substr(str_pad($operand2, $numBits, '0', STR_PAD_LEFT), -$numBits);
        $result = '';
        for ($index = 0; $index < $numBits; $index++) {
            $result .= $operand1[$index] === '1' && $operand2[$index] === '1' ? '1' : '0';
        }

        return $this->reduce($result);
    }

    /**
     * Calculate the bitwise OR of two non-negative integers represented in binary form.
     *
     * @param string $operand1
     * @param string $operand2
     *
     * @return string
     */
    public function orX($operand1, $operand2)
    {
        list($operand1, $operand2, $numBits) = $this->toSameLength($operand1, $operand2);
        $result = '';
        for ($index = 0; $index < $numBits; $index++) {
            $result .= $operand1[$index] === '1' || $operand2[$index] === '1' ? '1' : '0';
        }

        return $result;
    }

    /**
     * Compute 2 raised to the given exponent.
     *
     * If the result fits into a native PHP integer, an int is returned.
     * If the result exceeds PHP_INT_MAX, a string containing the exact decimal representation is returned.
     *
     * @param int $exponent The non-negative exponent
     *
     * @return int|numeric-string
     */
    public function pow2string($exponent)
    {
        if ($exponent < PHP_INT_SIZE * 8 - 1) {
            return 1 << $exponent;
        }
        $digits = array(1);
        for ($i = 0; $i < $exponent; $i++) {
            $carry = 0;
            foreach ($digits as $index => $digit) {
                $product = $digit * 2 + $carry;
                $digits[$index] = $product % 10;
                $carry = (int) ($product / 10);
            }
            if ($carry !== 0) {
                $digits[] = $carry;
            }
        }
        $result = implode('', array_reverse($digits));
        /** @var numeric-string $result */

        return $result;
    }

    /**
     * @param numeric-string|mixed $value
     *
     * @return numeric-string|'' empty string if $value is not a valid numeric string
     */
    public function normalizeIntegerString($value)
    {
        if (!is_string($value) || $value === '') {
            return '';
        }
        $sign = $value[0];
        if ($sign === '-' || $sign === '+') {
            $value = substr($value, 1);
        }
        $matches = null;
        if (!preg_match('/^0*([0-9]+)$/', $value, $matches)) {
            return '';
        }
        $numericString = $matches[1];
        if ($sign === '-' && $numericString !== '0') {
            $numericString = '-' . $numericString;
        }
        /** @var numeric-string $numericString */

        return $numericString;
    }

    /**
     * @param numeric-string $value a string that has been normalized with normalizeIntegerString()
     *
     * @return numeric-string
     */
    public function add1ToIntegerString($value)
    {
        if ($value[0] === '-') {
            if ($value === '-1') {
                return '0';
            }
            $digits = str_split(substr($value, 1));
            $i = count($digits) - 1;
            while ($i >= 0) {
                if ($digits[$i] !== '0') {
                    $digits[$i] = (string) ((int) $digits[$i] - 1);
                    break;
                }
                $digits[$i] = '9';
                $i--;
            }
            $imploded = implode('', $digits);
            if ($imploded[0] === '0') {
                $imploded = substr($imploded, 1);
            }
            $result = '-' . $imploded;
            /** @var numeric-string $result */

            return $result; // @phpstan-ignore varTag.nativeType
        }
        $digits = str_split($value);
        $carry = 1;
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $sum = (int) $digits[$i] + $carry;
            $digits[$i] = (string) ($sum % 10);
            $carry = (int) ($sum / 10);
            if ($carry === 0) {
                break;
            }
            if ($i === 0) {
                array_unshift($digits, (string) $carry);
            }
        }
        $result = implode('', $digits);
        /** @var numeric-string $result */

        return $result;
    }

    /**
     * Zero-padding of two non-negative integers represented in binary form, so that they have the same length.
     *
     * @param string $num1
     * @param string $num2
     *
     * @return array{string, string, int} The first array element is $num1 (padded), the first array element is $num2 (padded), the third array element is the number of bits
     */
    private function toSameLength($num1, $num2)
    {
        $num1 = $this->reduce($num1);
        $num2 = $this->reduce($num2);
        $numBits = max(strlen($num1), strlen($num2));

        return array(
            str_pad($num1, $numBits, '0', STR_PAD_LEFT),
            str_pad($num2, $numBits, '0', STR_PAD_LEFT),
            $numBits,
        );
    }
}
