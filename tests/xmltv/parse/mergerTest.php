<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\xmltv\tests\tools\xmltv\parse;

use datagutten\xmltv\tools\parse\merger;
use PHPUnit\Framework\TestCase;

class mergerTest extends TestCase
{
    public function testFind_program()
    {
        $merger = new merger(__DIR__.'/test_data', ['xmltv', 'xmltv_php']);
        $program = $merger->find_program(strtotime('2020-05-10 16:55 +02:00'), 'xd.disneychannel.no');
        $this->assertEquals('Phineas og Ferb', $program->{'title'});
        $this->assertEquals('Klimpaloonultimatumet', $program->{'sub-title'});
        $this->assertEquals('3.119.', $program->{'episode-num'});
    }

    public function testFind_program2()
    {
        $merger = new merger(__DIR__.'/test_data', ['xmltv', 'xmltv_php']);
        $program = $merger->find_program(strtotime('2020-06-05 05:00 +00:00'), 'xd.disneychannel.no');
        $this->assertEquals('Phineas og Ferb', $program->{'title'});
    }

    public function testFind_programSingleParser()
    {
        $merger = new merger(__DIR__.'/test_data', ['xmltv']);
        $program = $merger->find_program(strtotime('2020-06-05 05:00 +00:00'), 'xd.disneychannel.no');
        $this->assertEquals('Phineas og Ferb', $program->{'title'});
    }

    public function testInvalidFile()
    {
        $merger = new merger(__DIR__.'/test_data', ['xmltv_php', 'xmltv']);
        $program = $merger->find_program(strtotime('2019-11-23 11:00 +01:00'), 'natgeo.no');
        $this->assertEquals('Vinterveiens helter', $program->{'title'});
    }
}
