<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Created by PhpStorm.
 * User: abi
 * Date: 04.10.2019
 * Time: 15:00
 */

namespace datagutten\xmltv\tests\tools\xmltv\parse;

use datagutten\xmltv\tools\exceptions\ProgramNotFoundException;
use datagutten\xmltv\tools\parse\parser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class parserTest extends TestCase
{
    /**
     * @var parser
     */
    public $parser;
    public function setUp(): void
    {
        $this->parser = new parser(__DIR__.'/test_data', ['xmltv_php', 'xmltv']);
    }

    public function testIgnoreTimeZone()
    {
        $this->parser->ignore_timezone = true;
        $timestamp = $this->parser->strtotime('20191013063500 +0300');
        $this->assertEquals('06:35', date('H:i', $timestamp));
    }
    public function testRemoveTimeZoneInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parser->strtotime('2019101306350');
    }

    public function testFind_program()
    {
        $program = $this->parser->find_program(strtotime('2019-10-03 21:55'), 'natgeo.no');
        $this->assertEquals('Vinterveiens helter', $program->{'title'});
        $this->assertEquals('20191003220000 +0200', $program->attributes()->{'start'});
    }

    public function testFind_program2()
    {
        $program = $this->parser->find_program(strtotime('2019-10-04 00:55'), 'natgeo.no');
        $this->assertEquals('Vinterveiens helter', $program->{'title'});
        $this->assertEquals('20191004010000 +0200', $program->attributes()->{'start'});
    }

    public function testFindFirstProgram()
    {
        $program = $this->parser->find_program(strtotime('2020-05-10 02:00'), 'xd.disneychannel.no', 'next');
        $this->assertEquals('Furiki Wheels', $program->{'title'});
        $this->assertEquals('20200510060000 +0200', $program->attributes()->{'start'});
    }

    public function testFind_programNext()
    {
        $program = $this->parser->find_program(strtotime('2019-10-04 00:55'), 'natgeo.no', 'next');
        $this->assertEquals('Vinterveiens helter', $program->{'title'});
        $this->assertEquals('20191004010000 +0200', $program->attributes()->{'start'});
    }

    public function testCurrentProgram()
    {
        $program = $this->parser->find_program(strtotime('2020-05-10 08:35'), 'xd.disneychannel.no', 'now');
        $this->assertEquals('Phineas og Ferb', $program->{'title'});
        $this->assertEquals('20200510082900 +0200', $program->attributes()->{'start'});
    }

    public function testLastProgram()
    {
        $this->parser->debug = true;
        $this->expectOutputRegex("/Time to start:.+Returning last program\n/s");
        $program = $this->parser->find_program(strtotime('2020-05-10 23:00'), 'xd.disneychannel.no', 'nearest');
        $this->assertEquals('Space Chickens In Space S1', $program->{'title'});
        $this->assertEquals('20200510212900 +0200', $program->attributes()->{'start'});
    }

    public function testGet_programs()
    {
        $programs = $this->parser->get_programs('natgeo.no', strtotime('2019-10-04'));
        $this->assertIsArray($programs);
        $this->assertEquals('20191004000000 +0200', $programs[0]->attributes()->{'start'});
    }

    public function testGet_programsNotCombined()
    {
        $parser = new parser(__DIR__.'/test_data', ['xmltv']);
        $programs = $parser->get_programs('natgeo.no', strtotime('2019-10-04'), false);
        $this->assertIsArray($programs);
        $this->assertEquals('20191004060000 +0000', $programs[0]->attributes()->{'start'});
    }
    public function testProgramBeforeSchedule()
    {
        $this->expectException(ProgramNotFoundException::class);
        $this->expectExceptionMessage('Nothing on air at given time');
        $this->parser->find_program(strtotime('2019-10-11 00:45'), 'no.bbcentertainment.no', 'now');
    }
    public function testFileNotFound()
    {
        $this->expectException(ProgramNotFoundException::class);
        //$this->expectExceptionMessage('Nothing on air at given time');
        $this->expectExceptionMessageMatches('/File does not exist.+/');
        $this->parser->find_program(strtotime('2099-10-11 00:45'), 'no.bbcentertainment.no', 'now');
    }

    /**
     * The file in the default sub directory is invalid, but the second is valid
     * @throws ProgramNotFoundException
     */
    public function testFailToAlternateSubFolder()
    {
        $program = $this->parser->find_program(strtotime('2019-11-23 11:00 +01:00'), 'natgeo.no');
        $this->assertEquals('Vinterveiens helter', $program->{'title'});
    }

    /** @noinspection PhpParamsInspection */
    public function testSeason_episode()
    {
        $program = new stdClass();
        $program->{'episode-num'} = ['0.17.'];
        $string = parser::season_episode($program);
        $this->assertSame('S01E18', $string);

        $program->{'episode-num'} = ['0.17.'];
        $array = parser::season_episode($program, false);
        $this->assertSame(['season'=>1, 'episode'=>18], $array);

        $program->{'episode-num'} = ['.17/20.'];
        $string = parser::season_episode($program);
        $this->assertSame('EP18', $string);

        $program->{'episode-num'} = ['.17/20.'];
        $array = parser::season_episode($program, false);
        $this->assertSame(['season'=>0, 'episode'=>18], $array);

        $program->{'episode-num'} = ['.17.'];
        $string = parser::season_episode($program);
        $this->assertEmpty($string);

    }

    public function testCombine_days()
    {
        $parser = new parser(__DIR__.'/test_data', ['xmltv']);
        $day1 = $parser->files->load_file('natgeo.no', strtotime('2019-10-03'));
        $day2 = $parser->files->load_file('natgeo.no', strtotime('2019-10-04'));
        $day = $parser->combine_days(array($day1, $day2), '20191004');
        $this->assertIsArray($day);
        $this->assertEquals('20191004000000 +0000', $day[0]->attributes()['start']);
    }

    public function testCombine_days2()
    {
        $parser = new parser(__DIR__.'/test_data', ['xmltv']);
        $day = $parser->get_programs('natgeo.no', strtotime('2019-10-04'));
        $this->assertEquals('20191004000000 +0000', $day[0]->attributes()['start']);
    }

    public function testFilterPrograms()
    {
        $programs = $this->parser->get_programs('natgeo.no', strtotime('2019-10-04'));
        $programs_filtered = $this->parser->filter_programs($programs, 'Vinterveiens helter');
        $this->assertEquals('Vinterveiens helter', $programs_filtered[0]->{'title'});
        $this->assertNotEquals($programs[0]->{'title'}, $programs_filtered[0]->{'title'});
    }
}
