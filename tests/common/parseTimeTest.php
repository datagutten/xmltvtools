<?php

namespace datagutten\xmltv\tests\tools\common;

use datagutten\xmltv\tools\data\BaseElement;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

date_default_timezone_set('UTC');

class parseTimeTest extends TestCase
{
    function testIntTimestampNoTimezone()
    {
        $time = BaseElement::parseTime(1774173621);
        $this->assertEquals('2026-03-22T10:00:21+00:00', $time->format('c'));
    }

    function testIntTimestampTimezone()
    {
        $time = BaseElement::parseTime(1774173621, new DateTimeZone('Europe/Oslo'));
        $this->assertEquals('2026-03-22T11:00:21+01:00', $time->format('c'));
    }

    function testStringTimestampNoTimezone()
    {
        $time = BaseElement::parseTime('1774173621');
        $this->assertEquals('2026-03-22T10:00:21+00:00', $time->format('c'));
    }

    function testIsoStringWithTimezone()
    {
        $time = BaseElement::parseTime('2026-03-22T11:00:21+01:00', new DateTimeZone('UTC'));
        $this->assertEquals('2026-03-22T11:00:21+01:00', $time->format('c'));
    }

    function testIsoStringWithoutTimezone()
    {
        $time = BaseElement::parseTime('2026-03-22T11:00:21', new DateTimeZone('Europe/Oslo'));
        $this->assertEquals('2026-03-22T11:00:21+01:00', $time->format('c'));
    }

    function testMutableToImmutable()
    {
        $time = BaseElement::parseTime(new DateTime('2026-03-22T11:00:21+01:00'));
        $this->assertEquals('2026-03-22T11:00:21+01:00', $time->format('c'));
    }

    function testImmutable()
    {
        $time_in = new DateTimeImmutable('2026-03-22T11:00:21+01:00');
        $time = BaseElement::parseTime($time_in);
        $this->assertSame($time_in, $time);
        $this->assertEquals('2026-03-22T11:00:21+01:00', $time->format('c'));
    }
}
