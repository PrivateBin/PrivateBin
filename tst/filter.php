<?php
class filterTest extends PHPUnit_Framework_TestCase
{
    public function testFilterStripsSlashesDeeply()
    {
        $this->assertEquals(
            array("f'oo", "b'ar", array("fo'o", "b'ar")),
            filter::stripslashes_deep(array("f\\'oo", "b\\'ar", array("fo\\'o", "b\\'ar")))
        );
    }

    public function testFilterMakesSizesHumanlyReadable()
    {
        $this->assertEquals('1 B', filter::size_humanreadable(1));
        $this->assertEquals('1 000 B', filter::size_humanreadable(1000));
        $this->assertEquals('1.00 kiB', filter::size_humanreadable(1024));
        $this->assertEquals('1.21 kiB', filter::size_humanreadable(1234));
        $exponent = 1024;
        $this->assertEquals('1 000.00 kiB', filter::size_humanreadable(1000 * $exponent));
        $this->assertEquals('1.00 MiB', filter::size_humanreadable(1024 * $exponent));
        $this->assertEquals('1.21 MiB', filter::size_humanreadable(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 MiB', filter::size_humanreadable(1000 * $exponent));
        $this->assertEquals('1.00 GiB', filter::size_humanreadable(1024 * $exponent));
        $this->assertEquals('1.21 GiB', filter::size_humanreadable(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 GiB', filter::size_humanreadable(1000 * $exponent));
        $this->assertEquals('1.00 TiB', filter::size_humanreadable(1024 * $exponent));
        $this->assertEquals('1.21 TiB', filter::size_humanreadable(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 TiB', filter::size_humanreadable(1000 * $exponent));
        $this->assertEquals('1.00 PiB', filter::size_humanreadable(1024 * $exponent));
        $this->assertEquals('1.21 PiB', filter::size_humanreadable(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 PiB', filter::size_humanreadable(1000 * $exponent));
        $this->assertEquals('1.00 EiB', filter::size_humanreadable(1024 * $exponent));
        $this->assertEquals('1.21 EiB', filter::size_humanreadable(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 EiB', filter::size_humanreadable(1000 * $exponent));
        $this->assertEquals('1.00 ZiB', filter::size_humanreadable(1024 * $exponent));
        $this->assertEquals('1.21 ZiB', filter::size_humanreadable(1234 * $exponent));
        $exponent *= 1024;
        $this->assertEquals('1 000.00 ZiB', filter::size_humanreadable(1000 * $exponent));
        $this->assertEquals('1.00 YiB', filter::size_humanreadable(1024 * $exponent));
        $this->assertEquals('1.21 YiB', filter::size_humanreadable(1234 * $exponent));
    }
}
