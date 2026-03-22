<?php

namespace datagutten\xmltv\tests\tools\xmltv\build\xmltv;

use datagutten\xmltv\tools\build\programme;
use datagutten\xmltv\tools\build\tv;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

date_default_timezone_set('Europe/Oslo');

class buildTest extends TestCase
{
    public function testTV()
    {
        $tv = new tv('test.no', 'nb');
        $this->assertEquals('test.no', $tv->channel);
        $this->assertEquals('nb', $tv->language);
        $this->assertEquals('php-xmltv-grabber', $tv->generator);
    }

    public function testProgramme()
    {
        $tv = new tv('test.no', 'nb', 'test-grabber');
        $this->assertEquals('test-grabber', $tv->generator);
        $programme = new programme(strtotime('2020-01-27 18:00'), $tv);
        $this->assertSame($tv->language, $programme->default_lang);
        $this->assertInstanceOf(SimpleXMLElement::class, $programme->xml);
        $output = $tv->xml->asXML();
        $this->assertStringContainsString('start="20200127180000 +0100"', $output);
    }

    public function testProgrammeDateObj()
    {
        $tv = new tv('test.no', 'nb', 'test-grabber');
        $this->assertEquals('test-grabber', $tv->generator);
        $programme = new programme(new DateTimeImmutable('2020-01-27 16:00 +00:00'), $tv);
        $this->assertSame($tv->language, $programme->default_lang);
        $this->assertInstanceOf(SimpleXMLElement::class, $programme->xml);
        $output = $tv->xml->asXML();
        $this->assertStringContainsString('start="20200127160000 +0000"', $output);
    }
}
