<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\xmltv\tests\tools\xmltv\parse;


use datagutten\xmltv\tools\parse\program;
use PHPUnit\Framework\TestCase;

date_default_timezone_set('Europe/Oslo');
class programTest extends TestCase
{
    public function testFrom_eit()
    {
        $program = program::from_eit(__DIR__.'/test_data/20200605 0855 - Disney XD (N) - Phineas og Ferb x4.eit');
        $this->assertSame(4, $program->season);
        $this->assertSame(107, $program->episode);
        $this->assertSame('10:00', $program->start);
        $this->assertSame('10:29', $program->end);
        $this->assertStringContainsString('I mellomtiden prøver Candace', $program->description);
        $this->assertEmpty($program->categories);
    }

    public function testFrom_eit2()
    {
        $program = program::from_eit(__DIR__.'/test_data/20200611 0855 - Disney XD (N) - Phineas og Ferb x4.eit');
        $this->assertEmpty($program->season);
        $this->assertEmpty($program->episode);
        $this->assertSame('10:00', $program->start);
        $this->assertSame('10:29', $program->end);
        $this->assertStringContainsString('Candace greier ikke å motså trangen', $program->description);
        $this->assertEmpty($program->categories);
    }

    public function testFrom_eit_NoDescription()
    {
        $program = program::from_eit(__DIR__.'/test_data/20181207 0655 - Disney XD (N) - Milo Murphys lov.eit');
        $this->assertSame('Milo Murphys lov', $program->title);
        $this->assertEmpty($program->season);
        $this->assertEmpty($program->episode);
        $this->assertSame('07:00', $program->start);
        $this->assertSame('07:29', $program->end);
        $this->assertEmpty($program->description);
        $this->assertEmpty($program->categories);
    }

    public function testFormat_time()
    {
        $time = program::format_time(1544162400);
        $this->assertSame('07:00', $time);
    }

    public function testHeader()
    {
        $program = new program();
        $program->start = '06:00';
        $program->end = '06:29';
        $program->title = 'Milo Murphys lov';
        $this->assertSame('06:00-06:29 Milo Murphys lov', $program->header());
        $program->season = 1;
        $program->episode = 12;
        $this->assertSame('06:00-06:29 Milo Murphys lov S01E12', $program->header());
    }

    public function testFormat_episode()
    {
        $program = new program();
        $program->season = 2;
        $program->episode = 10;
        $this->assertEquals('S02E10', $program->format_episode());
    }

    public function testFrom_xmltv()
    {
        $xml = simplexml_load_file(__DIR__.'/test_data/natgeo.no/xmltv/2019/natgeo.no_2019-10-10.xml');
        $xml_program = $xml->{'programme'};
        $program = program::from_xmltv($xml_program);
        $this->assertInstanceOf(program::class, $program);
        $this->assertSame('Wicked Tuna', $program->title);
        $this->assertSame(['series'], $program->categories);
    }

    public function testMulipleCategories()
    {
        $xml = simplexml_load_file(__DIR__.'/test_data/program.xml');
        $xml_program = $xml->{'programme'};
        $program = program::from_xmltv($xml_program);
        $this->assertSame(['series', 'test'], $program->categories);
    }
}
