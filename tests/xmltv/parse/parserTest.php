<?php
/**
 * Created by PhpStorm.
 * User: abi
 * Date: 04.10.2019
 * Time: 15:00
 */

namespace datagutten\xmltv\tests\tools\xmltv\parse;

use datagutten\xmltv\tools\exceptions\ProgramNotFoundException;
use datagutten\xmltv\tools\parse\parser;
use FileNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class parserTest extends TestCase
{
    /**
     * @var parser
     */
    public $parser;
    public function setUp(): void
    {
        $config = file_get_contents(__DIR__.'/test_config.php');
        $config = str_replace('__DIR__', __DIR__, $config);
        file_put_contents(__DIR__.'/config.php', $config);
        set_include_path(__DIR__);
        $this->parser = new parser();
    }
    public function tearDown(): void
    {
        unlink(__DIR__.'/config.php');
    }

    public function testRemoveTimeZone()
    {
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

    public function testGet_programs()
    {
        $programs = $this->parser->get_programs('natgeo.no', strtotime('2019-10-04'));
        $this->assertIsArray($programs);
        $this->assertEquals('20191004000000 +0200', $programs[0]->attributes()->{'start'});
    }

    public function testGet_programsNotCombined()
    {
        $config = file_get_contents(__DIR__.'/config.php');
        $config = str_replace('xmltv_php', 'xmltv', $config);
        file_put_contents(__DIR__.'/config.php', $config);
        $this->parser->__construct();

        $programs = $this->parser->get_programs('natgeo.no', strtotime('2019-10-04'), false);
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

    public function testSeason_episode()
    {

    }

    public function testCombine_days()
    {
        $config = file_get_contents(__DIR__.'/config.php');
        $config = str_replace('xmltv_php', 'xmltv', $config);
        file_put_contents(__DIR__.'/config.php', $config);
        $this->parser->__construct();

        $parser = new parser();
        $day1 = $parser->files->load_file('natgeo.no', strtotime('2019-10-03'));
        $day2 = $parser->files->load_file('natgeo.no', strtotime('2019-10-04'));
        $day = $parser->combine_days(array($day1, $day2), '20191004');
        $this->assertIsArray($day);
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
