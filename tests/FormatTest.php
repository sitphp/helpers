<?php


namespace SitPHP\Helpers\Tests;


use SitPHP\Doubles\TestCase;
use SitPHP\Helpers\Format;

class FormatTest extends TestCase
{
    function testReadableTime()
    {
        $this->assertEquals("1 y", Format::readableTime(3600 * 24 * 365, 5, '%time% %unit%'));
        $this->assertEquals("1 d", Format::readableTime(3600 * 24, 5, '%time% %unit%'));
        $this->assertEquals("1.00278 h", Format::readableTime(3610, 5, '%time% %unit%'));
        $this->assertEquals("1.5 min", Format::readableTime(90, 5, '%time% %unit%'));
        $this->assertEquals("1 h", Format::readableTime(3600, 5, '%time% %unit%'));
        $this->assertEquals("30 s", Format::readableTime(30, 5, '%time% %unit%'));
        $this->assertEquals("435 ms", Format::readableTime(0.435, 5, '%time% %unit%'));
        $this->assertEquals("43.43233 s", Format::readableTime(43.432333, 5, '%time% %unit%'));
    }

    function testReadableSize()
    {
        $this->assertEquals("10 B", Format::readableSize(10, 5, '%size% %unit%'));
        $this->assertEquals("1.00098 KB", Format::readableSize(1025, 5, '%size% %unit%'));
        $this->assertEquals("1 MB", Format::readableSize(1048576, 5, '%size% %unit%'));
    }
}