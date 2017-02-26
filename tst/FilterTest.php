<?php

use Eris\Generator;
use PrivateBin\Filter;

class FilterTest extends PHPUnit_Framework_TestCase
{
    use Eris\TestTrait;

    public function testFilterStripsSlashesDeeply()
    {
        $this->assertEquals(
            array("f'oo", "b'ar", array("fo'o", "b'ar")),
            Filter::stripslashesDeep(array("f\\'oo", "b\\'ar", array("fo\\'o", "b\\'ar")))
        );
    }

    public function testFilterMakesTimesHumanlyReadable()
    {
        $this->forAll(
            Generator\nat(),
            Generator\oneOf(
                'sec', 'second', 'seconds'
            )
        )->then(
            function ($int, $unit) {
                $suffix = $int === 1 ? '' : 's';
                $this->assertEquals($int . ' second' . $suffix, Filter::formatHumanReadableTime($int . $unit));
            }
        );
        $this->forAll(
            Generator\nat(),
            Generator\oneOf(
                'min', 'minute', 'minutes'
            )
        )->then(
            function ($int, $unit) {
                $suffix = $int === 1 ? '' : 's';
                $this->assertEquals($int . ' minute' . $suffix, Filter::formatHumanReadableTime($int . $unit));
            }
        );
        $this->forAll(
            Generator\nat(),
            Generator\oneOf(
                'hour', 'hours', 'day', 'days', 'week', 'weeks',
                'month', 'months', 'year', 'years'
            )
        )->then(
            function ($int, $unit) {
                $suffix = $int === 1 ? '' : 's';
                $this->assertEquals($int . ' ' . rtrim($unit, 's') . $suffix, Filter::formatHumanReadableTime($int . $unit));
            }
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 30
     */
    public function testFilterFailTimesHumanlyReadable()
    {
        $this->forAll(
            Generator\string()
        )->then(
            function ($string) {
                Filter::formatHumanReadableTime($string);
            }
        );
    }

    public function testFilterMakesSizesHumanlyReadable()
    {
        $this->forAll(
            Generator\neg()
        )->then(
            function ($int) {
                $this->assertEquals(number_format($int, 0, '.', ' ') . ' B', Filter::formatHumanReadableSize($int));
            }
        );
        $from     = 0;
        $exponent = 1024;
        $to       = $exponent - 1;
        $this->forAll(
            Generator\choose($from, $to)
        )->then(
            function ($int) {
                $this->assertEquals(number_format($int, 0, '.', ' ') . ' B', Filter::formatHumanReadableSize($int));
            }
        );
        $from      = $exponent;
        $exponent *= 1024;
        $to        = $exponent - 1;
        $this->forAll(
            Generator\choose($from, $to),
            $from
        )->then(
            function ($int, $divisor) {
                $this->assertEquals(number_format($int / $divisor, 2, '.', ' ') . ' KiB', Filter::formatHumanReadableSize($int));
            }
        );
        $from      = $exponent;
        $exponent *= 1024;
        $to        = $exponent - 1;
        $this->forAll(
            Generator\choose($from, $to),
            $from
        )->then(
            function ($int, $divisor) {
                $this->assertEquals(number_format($int / $divisor, 2, '.', ' ') . ' MiB', Filter::formatHumanReadableSize($int));
            }
        );
        $from      = $exponent;
        $exponent *= 1024;
        $to        = $exponent - 1;
        $this->forAll(
            Generator\choose($from, $to),
            $from
        )->then(
            function ($int, $divisor) {
                $this->assertEquals(number_format($int / $divisor, 2, '.', ' ') . ' GiB', Filter::formatHumanReadableSize($int));
            }
        );
        $from      = $exponent;
        $exponent *= 1024;
        $to        = $exponent - 1;
        $this->forAll(
            Generator\choose($from, $to),
            $from
        )->then(
            function ($int, $divisor) {
                $this->assertEquals(number_format($int / $divisor, 2, '.', ' ') . ' TiB', Filter::formatHumanReadableSize($int));
            }
        );
        $from      = $exponent;
        $exponent *= 1024;
        $to        = $exponent - 1;
        $this->forAll(
            Generator\choose($from, $to),
            $from
        )->then(
            function ($int, $divisor) {
                $this->assertEquals(number_format($int / $divisor, 2, '.', ' ') . ' PiB', Filter::formatHumanReadableSize($int));
            }
        );
        $from      = $exponent;
        $exponent *= 1024;
        $to        = $exponent - 1;

        // on 64bit systems, this gets larger then PHP_INT_MAX, so PHP casts it
        // to double and the "choose" generator can't handle it
        if ($to > PHP_INT_MAX) {
            $this->assertEquals('1.00 EiB', Filter::formatHumanReadableSize($from));
            $this->assertEquals('1.23 EiB', Filter::formatHumanReadableSize(1.234 * $from));
            $this->assertEquals('1 000.00 EiB', Filter::formatHumanReadableSize(1000 * $from));
            $this->assertEquals('1.00 ZiB', Filter::formatHumanReadableSize($exponent));
            $this->assertEquals('1.23 ZiB', Filter::formatHumanReadableSize(1.234 * $exponent));
            $this->assertEquals('1 000.00 ZiB', Filter::formatHumanReadableSize(1000 * $exponent));
            $exponent *= 1024;
            $this->assertEquals('1.00 YiB', Filter::formatHumanReadableSize($exponent));
            $this->assertEquals('1.23 YiB', Filter::formatHumanReadableSize(1.234 * $exponent));
        } else {
            $this->forAll(
                Generator\choose($from, $to),
                $from
            )->then(
                function ($int, $divisor) {
                    $this->assertEquals(number_format($int / $divisor, 2, '.', ' ') . ' EiB', Filter::formatHumanReadableSize($int));
                }
            );
            $from      = $exponent;
            $exponent *= 1024;
            $to        = $exponent - 1;
            $this->forAll(
                Generator\choose($from, $to),
                $from
            )->then(
                function ($int, $divisor) {
                    $this->assertEquals(number_format($int / $divisor, 2, '.', ' ') . ' ZiB', Filter::formatHumanReadableSize($int));
                }
            );
            $from      = $exponent;
            $exponent *= 1024;
            $to        = $exponent - 1;
            $this->forAll(
                Generator\choose($from, $to),
                $from
            )->then(
                function ($int, $divisor) {
                    $this->assertEquals(number_format($int / $divisor, 2, '.', ' ') . ' YiB', Filter::formatHumanReadableSize($int));
                }
            );
        }
    }

    public function testSlowEquals()
    {
        $this->forAll(
            Generator\string()
        )->then(
            function ($string) {
                $this->assertTrue(Filter::slowEquals($string, $string), 'same string');
            }
        );
        $this->forAll(
            Generator\int()
        )->then(
            function ($int) {
                $this->assertTrue(Filter::slowEquals($int, $int), 'same integer');
            }
        );
        $this->forAll(
            Generator\float()
        )->then(
            function ($float) {
                $this->assertTrue(Filter::slowEquals($float, $float), 'same float');
            }
        );
        $this->forAll(
            Generator\string()
        )->then(
            function ($string) {
                $this->assertFalse(Filter::slowEquals($string, true), 'string and boolean true');
            }
        );
        $this->forAll(
            Generator\string()
        )->then(
            function ($string) {
                // false is casted into an empty string
                if ($string !== '') {
                    $this->assertFalse(Filter::slowEquals($string, false), 'string and boolean false');
                }
            }
        );
        $this->forAll(
            Generator\string(),
            Generator\int()
        )->then(
            function ($string, $int) {
                $this->assertFalse(Filter::slowEquals($string, $int), 'string and integer');
            }
        );
        $this->forAll(
            Generator\string(),
            Generator\float()
        )->then(
            function ($string, $float) {
                $this->assertFalse(Filter::slowEquals($string, $float), 'string and float');
            }
        );
        $this->forAll(
            Generator\string(),
            Generator\string()
        )->then(
            function ($string1, $string2) {
                if ($string1 !== $string2) {
                    $this->assertFalse(Filter::slowEquals($string1, $string2), 'different strings');
                }
            }
        );
        $this->forAll(
            Generator\string()
        )->then(
            function ($string) {
                $this->assertFalse(Filter::slowEquals($string, ' ' . $string), 'strings with space');
            }
        );
        $this->forAll(
            Generator\float()
        )->then(
            function ($float) {
                $this->assertFalse(Filter::slowEquals(strval($float), $float . '0'), 'floats as strings');
            }
        );
        $this->forAll(
            Generator\int()
        )->then(
            function ($int) {
                $this->assertFalse(Filter::slowEquals($int . 'e3', $int . '000'), 'integers as strings');
            }
        );

        // these two tests would be compared equal if casted to integers as they are larger then PHP_INT_MAX
        $this->assertFalse(Filter::slowEquals('9223372036854775807', '9223372036854775808'), 'large integers as strings');
        $this->assertFalse(Filter::slowEquals('61529519452809720693702583126814', '61529519452809720000000000000000'), 'larger integers as strings');
    }
}
