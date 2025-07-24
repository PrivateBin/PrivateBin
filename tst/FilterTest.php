<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Filter;

class FilterTest extends TestCase
{
    public function testFilterMakesTimesHumanlyReadable()
    {
        $this->assertEquals('5 minutes', Filter::formatHumanReadableTime('5min'));
        $this->assertEquals('90 seconds', Filter::formatHumanReadableTime('90sec'));
        $this->assertEquals('1 week', Filter::formatHumanReadableTime('1week'));
        $this->assertEquals('6 months', Filter::formatHumanReadableTime('6months'));
    }

    public function testFilterFailTimesHumanlyReadable()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(30);
        Filter::formatHumanReadableTime('five_minutes');
    }

    public function testFilterMakesSizesHumanlyReadable()
    {
        $this->assertEquals('1 B', Filter::formatHumanReadableSize(1));
        $this->assertEquals('1.00 kB', Filter::formatHumanReadableSize(1000));
        $this->assertEquals('1.02 kB', Filter::formatHumanReadableSize(1024));
        $this->assertEquals('1.23 kB', Filter::formatHumanReadableSize(1234));
        $exponent = 1000;
        $this->assertEquals('1.00 MB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.02 MB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.23 MB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1000;
        $this->assertEquals('1.00 GB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.02 GB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.23 GB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1000;
        $this->assertEquals('1.00 TB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.02 TB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.23 TB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1000;
        $this->assertEquals('1.00 PB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.02 PB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.23 PB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1000;
        $this->assertEquals('1.00 EB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.02 EB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.23 EB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1000;
        $this->assertEquals('1.00 ZB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.02 ZB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.23 ZB', Filter::formatHumanReadableSize(1234 * $exponent));
        $exponent *= 1000;
        $this->assertEquals('1.00 YB', Filter::formatHumanReadableSize(1000 * $exponent));
        $this->assertEquals('1.02 YB', Filter::formatHumanReadableSize(1024 * $exponent));
        $this->assertEquals('1.23 YB', Filter::formatHumanReadableSize(1234 * $exponent));
    }
}
