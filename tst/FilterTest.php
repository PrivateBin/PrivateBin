<?php

use PrivateBin\Filter;

class FilterTest extends PHPUnit_Framework_TestCase
{
    public function testFilterStripsSlashesDeeply()
    {
        $this->assertEquals(
            array("f'oo", "b'ar", array("fo'o", "b'ar")),
            Filter::stripslashesDeep(array("f\\'oo", "b\\'ar", array("fo\\'o", "b\\'ar")))
        );
    }

    public function testFilterMakesTimesHumanlyReadable()
    {
        $this->assertEquals('5 minutes', Filter::formatHumanReadableTime('5min'));
        $this->assertEquals('90 seconds', Filter::formatHumanReadableTime('90sec'));
        $this->assertEquals('1 week', Filter::formatHumanReadableTime('1week'));
        $this->assertEquals('6 months', Filter::formatHumanReadableTime('6months'));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 30
     */
    public function testFilterFailTimesHumanlyReadable()
    {
        Filter::formatHumanReadableTime('five_minutes');
    }

    public function testFilterMakesSizesHumanlyReadable()
    {
        $this->assertEquals('1 B', Filter::formatHumanReadableSize(1));
        $this->assertEquals('1 000 B', Filter::formatHumanReadableSize(1000));
        $this->assertEquals('1.00 KiB', Filter::formatHumanReadableSize(1024));
        $this->assertEquals('1.21 KiB', Filter::formatHumanReadableSize(1234));
        $exponent = 1024;
        $this->assertEquals('1 000.00 KiB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.00 MiB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.21 MiB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 MiB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.00 GiB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.21 GiB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 GiB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.00 TiB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.21 TiB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 TiB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.00 PiB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.21 PiB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 PiB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.00 EiB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.21 EiB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 EiB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.00 ZiB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.21 ZiB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 ZiB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.00 YiB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.21 YiB', Filter::formatHumanReadableSize(1234 * $exponent));
    }

    public function testSlowEquals()
    {
        $this->assertTrue(Filter::slowEquals('foo', 'foo'), 'same string');
        $this->assertFalse(Filter::slowEquals('foo', true), 'string and boolean');
        $this->assertFalse(Filter::slowEquals('foo', 0), 'string and integer');
        $this->assertFalse(Filter::slowEquals('123foo', 123), 'string and integer');
        $this->assertFalse(Filter::slowEquals('123foo', '123'), 'different strings');
        $this->assertFalse(Filter::slowEquals('6', ' 6'), 'strings with space');
        $this->assertFalse(Filter::slowEquals('4.2', '4.20'), 'floats as strings');
        $this->assertFalse(Filter::slowEquals('1e3', '1000'), 'integers as strings');
        $this->assertFalse(Filter::slowEquals('9223372036854775807', '9223372036854775808'), 'large integers as strings');
        $this->assertFalse(Filter::slowEquals('61529519452809720693702583126814', '61529519452809720000000000000000'), 'larger integers as strings');
    }
}
